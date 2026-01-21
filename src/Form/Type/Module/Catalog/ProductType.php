<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\SubCategory;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ProductType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProductType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private ?Request $request;
    private bool $isLayoutUser;
    private Website $website;

    /**
     * ProductType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->request = $this->coreLocator->request();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isLayoutUser = $user && in_array('ROLE_LAYOUT_CATALOGPRODUCT', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* @var Product $data */
        $data = $builder->getData();
        $isNew = !$data->getId();
        $displayCatalog = !$this->request->get('catalog') && $isNew || !$isNew;
        $this->website = $options['website'];
        $activesFields = $options['activesFields'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew && $displayCatalog ? 'col-md-9' : 'col-12',
            'class' => 'refer-code',
        ]);

        if ($displayCatalog) {

            $catalogs = $this->entityManager->getRepository(Catalog::class)->findBy(['website' => $this->website]);
            $builder->add('catalog', EntityType::class, [
                'label' => $this->translator->trans('Catalogue', [], 'admin'),
                'display' => 'basic',
                'placeholder' => count($catalogs) > 1 ? $this->translator->trans('Sélectionnez', [], 'admin') : false,
                'attr' => [
                    'data-placeholder' => count($catalogs) > 1 ? $this->translator->trans('Sélectionnez', [], 'admin') : false,
                    'group' => 'd-none',
                ],
                'class' => Catalog::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.website = :website')
                        ->setParameter('website', $this->website)
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'constraints' => [new Assert\NotBlank()],
            ]);

            if (!$isNew && in_array('categories', $activesFields)) {

                $builder->add('mainCategory', EntityType::class, [
                    'label' => $this->translator->trans('Catégorie principale', [], 'admin'),
                    'required' => false,
                    'display' => 'search',
                    'class' => Category::class,
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'attr' => [
                        'group' => 'col-12',
                        'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    ],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where('c.website = :website')
                            ->setParameter('website', $this->website)
                            ->orderBy('c.adminName', 'ASC');
                    },
                    'choice_label' => function ($entity) {
                        return strip_tags($entity->getAdminName());
                    },
                ]);

                $builder->add('categories', EntityType::class, [
                    'label' => $this->translator->trans('Catégories', [], 'admin'),
                    'required' => false,
                    'display' => 'search',
                    'class' => Category::class,
                    'attr' => [
                        'group' => 'col-12',
                        'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    ],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where('c.website = :website')
                            ->setParameter('website', $this->website)
                            ->orderBy('c.adminName', 'ASC');
                    },
                    'choice_label' => function ($entity) {
                        return strip_tags($entity->getAdminName());
                    },
                    'multiple' => true,
                ]);
            }
        }

        if ($isNew && $this->isLayoutUser) {
            $builder->add('customLayout', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Template personnalisé', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);
        }

        if (!$isNew) {

            if (in_array('sub-categories', $activesFields)) {
                $builder->add('subCategories', EntityType::class, [
                    'label' => $this->translator->trans('Sous-catégories', [], 'admin'),
                    'required' => false,
                    'display' => 'search',
                    'class' => SubCategory::class,
                    'attr' => [
                        'group' => 'col-12',
                        'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    ],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->leftJoin('s.catalogcategory', 'c')
                            ->where('c.website = :website')
                            ->setParameter('website', $this->website)
                            ->orderBy('s.adminName', 'ASC');
                    },
                    'group_by' => static function (SubCategory $entity): string {
                        return (string) $entity->getCatalogCategory()?->getAdminName();
                    },
                    'choice_label' => function ($entity) {
                        return strip_tags($entity->getAdminName().' ('.$entity->getCatalogCategory()->getAdminName().')');
                    },
                    'multiple' => true,
                ]);
            }

            $builder->add('promote', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Mettre en avant', [], 'admin'),
                'attr' => ['group' => 'col-md-2 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('catalogBeforePost', Type\HiddenType::class, [
                'mapped' => false,
                'data' => $data->getCatalog()->getId(),
            ]);

            $urls = new WidgetType\UrlsCollectionType($this->coreLocator);
            $urls->add($builder, ['display_seo' => true]);

            if (in_array('dates', $activesFields)) {
                $dates = new WidgetType\BetweenDatesType($this->coreLocator);
                $dates->add($builder);
            }

            $dates = new WidgetType\PublicationDatesType($this->coreLocator);
            $dates->add($builder, [
                'startGroup' => 'col-md-4',
                'endGroup' => 'col-md-4',
            ]);

            if (in_array('intls', $activesFields)) {
                $searchModule = $this->entityManager->getRepository(Module::class)->findOneBy(['slug' => 'search']);
                $searchModuleActive = $this->entityManager->getRepository(\App\Entity\Core\Configuration::class)->moduleExist($options['website'], $searchModule);
                $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
                $intls->add($builder, [
                    'fields' => $searchModuleActive ? ['title' => 'col-md-8', 'subTitle' => 'col-md-4', 'introduction', 'body', 'associatedWords'] : ['title' => 'col-md-8', 'subTitle' => 'col-md-4', 'introduction', 'body'],
                    'disableTitle' => true,
                ]);
            }

            if ($this->isLayoutUser) {
                $builder->add('customLayout', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Template personnalisé', [], 'admin'),
                    'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
                ]);
            }

            $builder->add('values', CollectionType::class, [
                'label' => false,
                'entry_type' => FeatureValueProductType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'block_name' => 'values',
                'entry_options' => [
                    'product' => $data,
                    'attr' => [
                        'class' => 'value',
                        'data-draggable' => $options['isDraggable'],
                    ],
                    'website' => $this->website,
                ],
            ]);

            if (in_array('lots', $activesFields)) {
                $builder->add('lots', CollectionType::class, [
                    'label' => false,
                    'entry_type' => LotType::class,
                    'allow_add' => true,
                    'prototype' => true,
                    'by_reference' => false,
                    'entry_options' => [
                        'attr' => [
                            'class' => 'lots',
                            'disableTitle' => true,
                            'button' => $this->translator->trans('Ajouter un lot', [], 'admin'),
                        ],
                        'website' => $this->website,
                    ],
                ]);
            }

            if (in_array('products', $activesFields)) {
                $builder->add('products', EntityType::class, [
                    'label' => $this->translator->trans('Produits', [], 'admin'),
                    'required' => false,
                    'class' => Product::class,
                    'attr' => [
                        'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    ],
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('p')
                            ->where('p.website = :website')
                            ->setParameter('website', $this->website)
                            ->orderBy('p.adminName', 'ASC');
                    },
                    'choice_label' => function ($entity) {
                        return strip_tags($entity->getAdminName());
                    },
                    'multiple' => true,
                    'display' => 'search',
                ]);
            }

            if (in_array('informations', $activesFields)) {
                $builder->add('information', InformationType::class);
            }
        } else {
            $save = new WidgetType\SubmitType($this->coreLocator);
            $save->add($builder);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'website' => null,
            'isDraggable' => false,
            'activesFields' => [],
            'translation_domain' => 'admin',
        ]);
    }
}
