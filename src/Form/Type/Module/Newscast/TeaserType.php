<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newscast;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Teaser;
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
    private ?Website $website = null;

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
            $adminNameGroup = 'col-md-10';
        } elseif (!$isNew) {
            $adminNameGroup = 'col-md-6';
        }

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $adminNameGroup,
            'slugGroup' => 'col-sm-2',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {
            if ($this->isInternalUser) {
                $builder->add('nbrItems', Type\IntegerType::class, [
                    'label' => $this->translator->trans("Nombre d'actualités par teaser", [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                        'group' => 'col-md-3',
                        'data-config' => true,
                    ],
                ]);

                $builder->add('itemsPerSlide', Type\IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->trans("Nombre d'actualités par slide", [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                        'group' => 'col-md-3',
                        'data-config' => true,
                    ],
                ]);

                $builder->add('orderBy', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Ordonner les actualités par', [], 'admin'),
                    'display' => 'search',
                    'attr' => ['group' => 'col-md-3', 'data-config' => true],
                    'choices' => [
                        $this->translator->trans('Dates de publication (croissantes)', [], 'admin') => 'publicationStart-asc',
                        $this->translator->trans('Dates de publication (décroissantes)', [], 'admin') => 'publicationStart-desc',
                        $this->translator->trans('Dates (croissantes)', [], 'admin') => 'startDate-asc',
                        $this->translator->trans('Dates (décroissantes)', [], 'admin') => 'startDate-desc',
                        $this->translator->trans('Positions (croissantes)', [], 'admin') => 'position-asc',
                        $this->translator->trans('Positions (décroissantes)', [], 'admin') => 'position-desc',
                    ],
                ]);

                $builder->add('formatDate', WidgetType\FormatDateType::class, [
                    'required' => true,
                    'attr' => ['group' => 'col-md-3', 'data-config' => true],
                ]);

                $builder->add('fields', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Champs à afficher', [], 'admin'),
                    'required' => false,
                    'expanded' => false,
                    'display' => 'search',
                    'multiple' => true,
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'attr' => [
                        'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                        'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                        'group' => 'col-md-9',
                        'data-config' => true,
                    ],
                    'choices' => [
                        $this->translator->trans('Titre du teaser', [], 'admin') => 'teaser-title',
                        $this->translator->trans('Image', [], 'admin') => 'image',
                        $this->translator->trans('Titre', [], 'admin') => 'title',
                        $this->translator->trans('Sous-titre', [], 'admin') => 'sub-title',
                        $this->translator->trans('Catégorie', [], 'admin') => 'category',
                        $this->translator->trans('Introduction', [], 'admin') => 'introduction',
                        $this->translator->trans('Description', [], 'admin') => 'body',
                        $this->translator->trans('Date', [], 'admin') => 'date',
                        $this->translator->trans('Lien vers fiche', [], 'admin') => 'card-link',
                        $this->translator->trans('Lien vers index', [], 'admin') => 'index-link',
                    ],
                ]);

                $builder->add('template', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Affichage', [], 'admin'),
                    'display' => 'search',
                    'choices' => [
                        $this->translator->trans('Slider', [], 'admin') => 'slider',
                        $this->translator->trans('Liste', [], 'admin') => 'list',
                        $this->translator->trans('Onglets verticaux', [], 'admin') => 'vertical',
                    ],
                    'attr' => ['group' => 'col-md-3', 'data-config' => true],
                ]);

                $builder->add('template', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Affichage', [], 'admin'),
                    'display' => 'search',
                    'choices' => [
                        $this->translator->trans('Slider', [], 'admin') => 'slider',
                        $this->translator->trans('Liste', [], 'admin') => 'list',
                        $this->translator->trans('Onglets verticaux', [], 'admin') => 'vertical',
                    ],
                    'attr' => ['group' => 'col-md-3', 'data-config' => true],
                ]);

                $builder->add('promote', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Afficher uniquement les actualités mis en avant', [], 'admin'),
                    'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
                ]);

                $builder->add('promoteFirst', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Mettre en avant la première actualité', [], 'admin'),
                    'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
                ]);

                $builder->add('displayFilters', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Afficher les filtres', [], 'admin'),
                    'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
                ]);
            }

            $builder->add('categories', EntityType::class, [
                'label' => $this->translator->trans('Catégories', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'class' => Category::class,
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.website = :website')
                        ->setParameter(':website', $this->website)
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
            ]);

            $builder->add('matchCategories', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Appartenant à toutes les catégories', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('asEvents', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans("Teaser d'événements", [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('pastEvents', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher les événements passés', [], 'admin'),
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
