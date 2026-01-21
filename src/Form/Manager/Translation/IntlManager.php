<?php

declare(strict_types=1);

namespace App\Form\Manager\Translation;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;

/**
 * IntlManager.
 *
 * Manage intl admin form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => IntlManager::class, 'key' => 'intl_form_manager'],
])]
class IntlManager
{
    /**
     * IntlManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * Post intl.
     *
     * @throws NonUniqueResultException
     */
    public function post(FormInterface $form, Website $website, bool $isNew = false): void
    {
        $entity = $form->getData();
        $defaultLocale = $website->getConfiguration()->getLocale();
        $classname = str_replace('Proxies\__CG__\\', '', get_class($entity));
        $interface = !is_numeric($entity->getId()) ? $this->coreLocator->interfaceHelper()->generate($classname) : [];
        $setTitle = $interface['prePersistTitle'] ?? true;

        if (method_exists($entity, 'getIntls')) {
            $defaultIntl = $this->getDefaultIntl($entity, $defaultLocale);
            if ($defaultIntl) {
                foreach ($entity->getIntls() as $intl) {
                    if ($intl->getLocale() !== $defaultLocale) {
                        $this->setTitleForce($intl, $defaultIntl);
                    } elseif ((!$intl->getId() || $isNew) && method_exists($entity, 'getAdminName') && $entity->getAdminName() && $setTitle) {
                        $intl->setTitle($entity->getAdminName());
                    }
                }
            } elseif (method_exists($entity, 'getAdminName') && $entity->getAdminName()) {
                $intlData = $this->coreLocator->metadata($entity, 'intls');
                $intl = new ($intlData->targetEntity)();
                $intl->setLocale($defaultLocale);
                $intl->setWebsite($website);
                if (method_exists($intl, $intlData->setter)) {
                    $setter = $intlData->setter;
                    $intl->$setter($entity);
                }
                if ($setTitle) {
                    $intl->setTitle($entity->getAdminName());
                }
                $entity->addIntl($intl);
            }
        } elseif ($entity && method_exists($entity, 'getIntl') && $entity->getIntl() && method_exists($entity->getIntl(), 'getLocale') && !$entity->getIntl()->getLocale()) {
            $intl = $entity->getIntl();
            $intl->setLocale($entity->getLocale());
        }
    }

    /**
     * Synchronize locale intl before Form render.
     */
    public function synchronizeLocales(mixed $entity, Website $website): void
    {
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        if (method_exists($entity, 'getIntls')) {
            $defaultIntl = $this->getDefaultIntl($entity, $defaultLocale);
            if (!$defaultIntl && $entity instanceof Block) {
                $defaultIntl = $this->addIntl($website, $entity, $defaultLocale, $defaultLocale);
            }
            foreach ($configuration->getAllLocales() as $locale) {
                if ($locale !== $defaultLocale) {
                    $existing = $this->localeExist($entity, $locale);
                    if (!$existing && $defaultIntl) {
                        $this->addIntl($website, $entity, $locale, $defaultLocale, $defaultIntl);
                    } elseif ($existing && !$existing->getId()) {
                        $this->entityManager->persist($existing);
                        if (!$this->coreLocator->request()->isMethod('post')) {
                            $this->entityManager->flush();
                            $this->entityManager->refresh($existing);
                        }
                    }
                }
            }
        }
    }

    /**
     * To add intl.
     */
    private function addIntl(Website $website, mixed $entity, string $locale, string $defaultLocale, mixed $defaultIntl = null): mixed
    {
        $intlData = $this->coreLocator->metadata($entity, 'intls');
        $referIntl = $defaultIntl ?: new ($intlData->targetEntity)();
        $intl = new ($intlData->targetEntity)();
        $intl->setLocale($locale);
        $intl->setWebsite($website);

        if (method_exists($intl, $intlData->setter)) {
            $setter = $intlData->setter;
            $intl->$setter($entity);
        }

        if ($entity instanceof Block && $locale === $defaultLocale && 'title-header' === $entity->getBlockType()->getSlug()) {
            $intl->setTitleForce(1);
        } else {
            $this->setTitleForce($intl, $referIntl);
        }

        $entity->addIntl($intl);

        $this->entityManager->persist($entity);

        if (!$this->coreLocator->request()->isMethod('post')) {
            $this->entityManager->flush();
            $this->entityManager->refresh($entity);
        }

        return $intl;
    }

    /**
     * Get default locale intl.
     */
    private function getDefaultIntl(mixed $entity, string $defaultLocale): mixed
    {
        foreach ($entity->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                return $intl;
            }
        }

        return false;
    }

    /**
     * Check if intl locale exist.
     */
    private function localeExist(mixed $entity, string $locale): mixed
    {
        foreach ($entity->getIntls() as $existingIntl) {
            if ($existingIntl->getLocale() === $locale) {
                return $existingIntl;
            }
        }

        return false;
    }

    /**
     * Set title force.
     */
    private function setTitleForce(mixed $intl, mixed $defaultIntl): void
    {
        if ($defaultIntl->getTitleForce() && !$intl->getTitleForce()) {
            $intl->setTitleForce($defaultIntl->getTitleForce());
            $this->entityManager->persist($intl);
        }
    }
}
