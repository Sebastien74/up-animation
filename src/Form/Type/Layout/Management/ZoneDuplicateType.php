<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use App\Entity\Layout\Zone;
use App\Repository\Core\WebsiteRepository;
use App\Service\Core\InterfaceHelper;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ZoneDuplicateType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZoneDuplicateType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private bool $multiSites;
    private ?Request $request;

    /**
     * ZoneDuplicateType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository $websiteRepository,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->multiSites = count($this->websiteRepository->findAll()) > 1;
        $this->request = $this->coreLocator->request();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $buildConfiguration = $this->getBuildConfiguration();

        $builder->add($buildConfiguration['name'], EntityType::class, [
            'mapped' => false,
            'display' => 'search',
            'label' => $this->translator->trans('Destination', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'class' => $buildConfiguration['classname'],
            'query_builder' => function (EntityRepository $er) {
                $referEntity = new ($er->getClassName())();
                $qb = $er->createQueryBuilder('e')
                    ->leftJoin('e.website', 'w')
                    ->orderBy('e.adminName', 'ASC');
                if (method_exists($referEntity, 'getUrls')) {
                    $qb->leftJoin('e.urls', 'u')
                        ->andWhere('u.archived = :archived')
                        ->setParameter('archived', false);
                }
                return $qb;
            },
            'attr' => ['group' => 'disable-asterisk col-12 text-center'],
            'choice_label' => function ($page) {
                if ($this->multiSites) {
                    return strip_tags($page->getAdminName()).' ('.$page->getWebsite()->getAdminName().')';
                }

                return strip_tags($page->getAdminName());
            },
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('zone', EntityType::class, [
            'mapped' => false,
            'label' => false,
            'attr' => ['class' => 'd-none'],
            'class' => Zone::class,
            'data' => $options['duplicate_entity'],
            'choice_label' => function ($entity) {
                $label = $entity->getAdminName() ? $entity->getAdminName() : 'zone-'.$entity->getId();
                return strip_tags($label);
            },
        ]);
    }

    /**
     * Get build configuration.
     *
     * @throws NonUniqueResultException
     */
    private function getBuildConfiguration(): array
    {
        /** @var Zone $zone */
        $website = $this->entityManager->getRepository(Website::class)->find($this->request->get('website'));
        /** @var Zone $zone */
        $zone = $this->entityManager->getRepository(Zone::class)->find($this->request->get('zone'));
        $layout = $zone->getLayout();

        $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metasData as $metadata) {
            $classname = $metadata->getName();
            $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
            if (!str_contains($classname, 'Layout') && is_object($baseEntity) && method_exists($baseEntity, 'getLayout')) {
                $interface = $this->interfaceHelper->generate($classname);
                if (!empty($interface['masterField']) && 'website' === $interface['masterField']) {
                    $entities = $this->entityManager->getRepository($classname)->findBy(['website' => $website]);
                } else {
                    $entities = $this->entityManager->getRepository($classname)->findAll();
                }
                foreach ($entities as $entity) {
                    $entityLayout = $entity->getLayout();
                    if ($entityLayout instanceof Layout && $entityLayout->getId() === $layout->getId() && !empty($interface['name'])) {
                        return [
                            'name' => $interface['name'],
                            'classname' => $classname,
                        ];
                    }
                }
            }
        }

        return [
            'name' => 'page',
            'classname' => Page::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Zone::class,
            'website' => null,
            'duplicate_entity' => null,
            'translation_domain' => 'admin',
            'allow_extra_fields' => true,
        ]);
    }
}
