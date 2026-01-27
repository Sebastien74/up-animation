<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\Listing;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\SubCategory;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ListingType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ListingType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?UserInterface $user;
    private bool $isInternalUser;
    private Website $website;

    /**
     * ApiType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->user = $coreLocator->user();
        $this->isInternalUser = $this->user && in_array('ROLE_INTERNAL', $this->user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();
        $this->website = $options['website'];
        $displayMapBtn = false;
        $catalogs = $this->coreLocator->em()->getRepository(Catalog::class)->findAll();
        foreach ($catalogs as $catalog) {
            if (in_array('informations', $catalog->getTabs())) {
                $displayMapBtn = true;
                break;
            }
        }

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-7',
            'slugGroup' => 'col-md-2',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {

            $builder->add('itemsPerPage', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nombre de produits par page', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'group' => 'col-md-3',
                    'data-config' => true,
                ],
            ]);

            $builder->add('orderBy', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Ordonner par', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Position', [], 'admin') => 'position',
                    $this->translator->trans('Titre', [], 'admin') => 'title',
                    $this->translator->trans('Date', [], 'admin') => 'startDate',
                    $this->translator->trans('Date de création', [], 'admin') => 'createdAt',
                    $this->translator->trans('Date de début de publication', [], 'admin') => 'publicationStart',
                    $this->translator->trans('Aléatoire', [], 'admin') => 'random',
                ],
                'attr' => ['group' => 'col-md-3', 'data-config' => true],
            ]);

            $builder->add('orderSort', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Trier par ordre', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Croissant', [], 'admin') => 'ASC',
                    $this->translator->trans('Décroissant', [], 'admin') => 'DESC',
                ],
                'attr' => ['group' => 'col-md-3', 'data-config' => true],
            ]);

            $builder->add('display', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Affichage des filtres', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('ConfigurationModel des types de filtres', [], 'admin') => 'configuration',
                    $this->translator->trans('Tout', [], 'admin') => 'all',
                    $this->translator->trans('Désactiver', [], 'admin') => 'disable',
                ],
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('catalogs', EntityType::class, [
                'label' => $this->translator->trans('Filtres des produits par catalogues', [], 'admin'),
                'required' => false,
                'class' => Catalog::class,
                'attr' => [
                    'group' => 'col-md-9',
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
                'display' => 'search',
            ]);

            $builder->add('searchCatalogs', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Type de filtre (Catalogues)', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getSelectChoices('searchCatalogs'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('products', EntityType::class, [
                'label' => $this->translator->trans('Filtres par produits', [], 'admin'),
                'required' => false,
                'class' => Product::class,
                'attr' => [
                    'group' => 'col-md-9',
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
                'display' => 'search',
            ]);

            $builder->add('searchProducts', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Type de filtre (Produits)', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getSelectChoices('searchProducts'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('categories', EntityType::class, [
                'label' => $this->translator->trans('Filtres des produits par catégories', [], 'admin'),
                'required' => false,
                'class' => Category::class,
                'attr' => [
                    'group' => 'col-md-9',
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
                'display' => 'search',
            ]);

            $builder->add('searchCategories', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Type de filtre (Catégories)', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getSelectChoices('searchCategories'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('subCategories', EntityType::class, [
                'label' => $this->translator->trans('Filtres des produits par sous-catégories', [], 'admin'),
                'required' => false,
                'class' => SubCategory::class,
                'attr' => [
                    'group' => 'col-md-9',
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->leftJoin('s.catalogcategory', 'c')
                        ->where('c.website = :website')
                        ->setParameter('website', $this->website)
                        ->addSelect('c')
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
                'display' => 'search',
            ]);

            $builder->add('searchSubCategories', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Type de filtre (Sous-catégories)', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getSelectChoices('searchSubCategories'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('features', EntityType::class, [
                'label' => $this->translator->trans('Filtres des produits par caractéristiques', [], 'admin'),
                'required' => false,
                'class' => Feature::class,
                'attr' => [
                    'group' => 'col-md-9',
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.website = :website')
                        ->setParameter('website', $this->website)
                        ->orderBy('c.position', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
                'display' => 'search',
            ]);

            $builder->add('searchFeatures', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Type de filtre (Caractéristiques)', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getSelectChoices('searchFeatures'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('updateFields', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Mettre à jour les sélecteurs', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('counter', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer le compteur de résultats', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('displayLabel', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher les labels', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('searchText', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer la recherche par mots-clés', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('groupByCategories', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Grouper par catégories', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('scrollInfinite', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Scroll infinite', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('showMoreBtn', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Bouton voir plus', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('combineFieldsText', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Recherche mots-clés et flitres combinés', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            if ($displayMapBtn) {
                $builder->add('showMap', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Aficher sur une carte', [], 'admin'),
                    'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
                ]);
            }

            $builder->add('featuresValues', CollectionType::class, [
                'label' => false,
                'entry_type' => ListingFeatureValueType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'block_name' => 'values',
                'entry_options' => [
                    'attr' => [
                        'class' => 'feature',
                        'icon' => 'fal filter',
                        'group' => 'col-md-4',
                        'caption' => $this->translator->trans('Filtres des produits par valeurs', [], 'admin'),
                        'button' => $this->translator->trans('Ajouter une valeur', [], 'admin'),
                    ],
                    'website' => $options['website'],
                ],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title'],
            ]);

            $builder->add('icon', WidgetType\IconType::class, [
                'label' => $this->translator->trans("Icône du titre", [], 'admin'),
                'required' => false,
                'attr' => ['class' => 'select-icons', 'group' => 'col-md-3'],
            ]);

            $builder->add('iconBackground', WidgetType\BackgroundColorSelectType::class, [
                'label' => $this->translator->trans("Couleur de fond de l'icône du titre", [], 'admin'),
                'attr' => ['class' => ' select-icons', 'group' => 'col-md-3'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get select choices.
     */
    private function getSelectChoices(string $fieldName): array
    {
        $choices = [
            $this->translator->trans('Ne pas afficher les filtres', [], 'admin') => null,
            $this->translator->trans('Automatique', [], 'admin') => 'auto',
            $this->translator->trans('Sélécteur choix unique', [], 'admin') => 'select-uniq',
            $this->translator->trans('Sélécteur choix multiple', [], 'admin') => 'select-multiple',
            $this->translator->trans('Radios boutons', [], 'admin') => 'radios',
            $this->translator->trans('Cases à cocher', [], 'admin') => 'checkboxes',
            $this->translator->trans('Liens', [], 'admin') => 'links',
        ];

        if ('searchSubCategories' === $fieldName) {
            $entries =
                array_slice($choices, 0, 4, true)
                + [
                    $this->translator->trans('Sélecteur à choix unique groupé par catégories', [], 'admin') => 'select-uniq-by-categories',
                    $this->translator->trans('Radios boutons groupés par catégories', [], 'admin') => 'radios-by-categories',
                ]
                + array_slice($choices, 4, null, true);
            $choices = $entries;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Listing::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
