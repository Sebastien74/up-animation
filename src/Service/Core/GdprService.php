<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Website;
use App\Entity\Module\Form\ContactForm;
use App\Entity\Module\Form\ContactStepForm;
use App\Entity\Module\Newsletter\Email;
use App\Form\Manager\Module\CampaignManager;
use App\Form\Manager\Security\Front\RegisterManager;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * GdprService.
 *
 * Manage Gdpr process
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GdprService
{
    private const bool REMOVE_ANONYMIZED = true;

    /**
     * GdprService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CronSchedulerService $cronSchedulerService,
        private readonly CampaignManager $campaignManager,
        private readonly RegisterManager $frontRegisterManager,
    ) {
    }

    /**
     * To remove old data.
     *
     * @throws \Exception
     */
    public function removeData(Website $website, ?InputInterface $input = null, ?string $command = null): void
    {
        $frequency = $website->getConfiguration()->getGdprFrequency();
        if ($frequency > 0) {
            $anonymized = [ContactForm::class, ContactStepForm::class];
            $namespaces = [ContactForm::class, ContactStepForm::class, Email::class];
            $datetime = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $interval = new \DateInterval('P'.$frequency.'D');
            $datetime->sub($interval);
            foreach ($namespaces as $namespace) {
                $entities = $this->coreLocator->em()->getRepository($namespace)->createQueryBuilder('e')
                    ->andWhere('e.createdAt <= :datetime')
                    ->setParameter('datetime', $datetime)
                    ->getQuery()
                    ->getResult();
                foreach ($entities as $entity) {
                    $this->removeAttachments($entity);
                    if (in_array($namespace, $anonymized)) {
                        $this->setFields($entity, $frequency);
                    } else {
                        $this->coreLocator->em()->remove($entity);
                    }
                    $this->coreLocator->em()->flush();
                }
                $this->cronSchedulerService->logger('[OK] '.$namespace.' successfully cleared.', $input);
            }
            $this->campaignManager->removeExpiredToken();
            $this->frontRegisterManager->removeExpiredToken();
            $this->cronSchedulerService->logger('[EXECUTED] '.$command, $input);
        }
    }

    /**
     * Remove attachments.
     */
    private function removeAttachments(mixed $entity): void
    {
        $filesystem = new Filesystem();
        if ($entity instanceof ContactForm || $entity instanceof ContactStepForm) {
            foreach ($entity->getContactValues() as $value) {
                if (preg_match('/public\/uploads/', $value->getValue())) {
                    $formId = $entity instanceof ContactForm ? $entity->getForm()->getId() : $entity->getStepform()->getId();
                    $formType = $entity instanceof ContactForm ? 'forms' : 'steps-forms';
                    $website = $entity instanceof ContactForm ? $entity->getForm()->getWebsite() : $entity->getStepform()->getWebsite();
                    $fileDirname = $this->coreLocator->projectDir().'/public/uploads/'.$website->getUploadDirname().'/emails/'.$formType.'/'.$formId.'/contacts/'.$entity->getId().'/';
                    $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
                    if ($filesystem->exists($fileDirname)) {
                        $filesystem->remove($fileDirname);
                    }
                }
            }
        }
    }

    /**
     * To set fields.
     */
    private function setFields(mixed $entity, int $frequency): void
    {
        $anonymized = ['email', 'phone', 'contactValues'];
        foreach ($anonymized as $field) {
            $getter = 'get'.ucfirst($field);
            $setter = 'set'.ucfirst($field);
            if (method_exists($entity, $getter)) {
                $value = $entity->$getter();
                if ($value instanceof PersistentCollection) {
                    foreach ($value as $item) {
                        if (method_exists($item, 'getConfiguration') && $item->getConfiguration()) {
                            $configuration = $item->getConfiguration();
                            if (method_exists($configuration, 'isAnonymize') && $configuration->isAnonymize() && method_exists($item, 'setValue')) {
                                $item->setValue($this->anonymize($item->getValue(), $frequency));
                                $this->coreLocator->em()->persist($item);
                            }
                        } elseif (method_exists($item, $setter)) {
                            $item->$setter($this->anonymize($value, $frequency));
                            $this->coreLocator->em()->persist($item);
                        }
                    }
                } elseif (method_exists($entity, $setter)) {
                    $entity->$setter($this->anonymize($value, $frequency));
                    $this->coreLocator->em()->persist($entity);
                }
            }
        }
    }

    /**
     * To anonymize value.
     */
    private function anonymize(string $value, int $frequency): ?string
    {
        $prefix = self::REMOVE_ANONYMIZED ? 'Données supprimée (RGPD limite '.$frequency.' jours)' : 'Données anonymisée (RGPD limite '.$frequency.' jours) : ';
        return self::REMOVE_ANONYMIZED ? $prefix : $prefix.hash('sha256', $value);
    }
}
