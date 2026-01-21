<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\SubCategory;
use App\Entity\Module\Catalog\Teaser;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TeaserType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TeaserType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;
    private Website $website;

    /**
     * TeaserType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();
        $this->website = $options['website'];

        $adminNameGroup = 'col-12';
        if (!$isNew && $this->isInternalUser) {
            $adminNameGroup = 'col-md-9';
        }

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $adminNameGroup,
            'slugGroup' => 'col-md-3',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {
            if ($this->isInternalUser) {

                $builder->add('template', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Affichage', [], 'admin'),
                    'display' => 'search',
                    'choices' => [
                        $this->translator->trans('Slider', [], 'admin') => 'slider',
                        $this->translator->trans('Liste', [], 'admin') => 'list',
                    ],
                    'attr' => ['group' => 'col-md-3', 'data-config' => true],
                ]);

                $builder->add('nbrItems', Type\IntegerType::class, [
                    'label' => $this->translator->trans('Nombre de produits par teaser', [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                        'group' => 'col-md-3',
                        'data-config' => true,
                    ],
                ]);

                $builder->add('itemsPerSlide', Type\IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('Nombre de produits par slide', [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                        'group' => 'col-md-3',
                        'data-config' => true,
                    ],
                ]);

                $builder->add('orderBy', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Ordonner les produits par', [], 'admin'),
                    'display' => 'search',
                    'attr' => ['group' => 'col-md-3', 'data-config' => true],
                    'choices' => [
                        $this->translator->trans('Dates de publication (croissantes)', [], 'admin') => 'publicationStart-asc',
                        $this->translator->trans('Dates de publication (décroissantes)', [], 'admin') => 'publicationStart-desc',
                        $this->translator->trans('Dates (croissantes)', [], 'admin') => 'startDate-asc',
                        $this->translator->trans('Dates (décroissantes)', [], 'admin') => 'startDate-desc',
                        $this->translator->trans('Positions (croissantes)', [], 'admin') => 'position-asc',
                        $this->translator->trans('Positions (décroissantes)', [], 'admin') => 'position-desc',
                        $this->translator->trans('Aléatoire', [], 'admin') => 'random',
                    ],
                ]);

                $builder->add('promote', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Afficher uniquement les produits mis en avant', [], 'admin'),
                    'attr' => ['group' => 'col-md-4', 'class' => 'w-100', 'data-config' => true],
                ]);
            }

            $builder->add('catalogs', EntityType::class, [
                'label' => $this->translator->trans('Catalogues', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'class' => Catalog::class,
                'attr' => [
                    'group' => 'col-md-6',
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

            $builder->add('categories', EntityType::class, [
                'label' => $this->translator->trans('Catégories', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'class' => Category::class,
                'attr' => [
                    'group' => 'col-md-6',
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

            $displaySubCategories = false;
            $catalogs = $this->coreLocator->em()->getRepository(Catalog::class)->findBy(['website' => $this->website]);
            foreach ($catalogs as $catalog) {
                if (in_array('sub-categories', $catalog->getTabs())) {
                    $displaySubCategories = true;
                    break;
                }
            }

            if ($displaySubCategories) {
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
                    'choice_label' => function ($entity) {
                        return strip_tags($entity->getAdminName().' ('.$entity->getCatalogCategory()->getAdminName().')');
                    },
                    'multiple' => true,
                ]);
            }

            $builder->add('matchCategories', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Appartenant à toutes les catégories', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Teaser::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
