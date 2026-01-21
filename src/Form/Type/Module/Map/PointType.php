<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Map;

use App\Entity\Core\Website;
use App\Entity\Media\Folder;
use App\Entity\Module\Map\Category;
use App\Entity\Module\Map\Point;
use App\Entity\Module\Map\PointGeoJson;
use App\Entity\Module\Map\PointMediaRelation;
use App\Form\Type\Information\PhoneType;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PointType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PointType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;

    /**
     * PointType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Point $point */
        $point = $builder->getData();
        $map = $point->getMap();
        $isNew = !$point->getId();
        $website = $options['website'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-8',
        ]);

        if (!$isNew) {
            $builder->add('marker', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Marqueur', [], 'admin'),
                'choices' => $this->getMarkers($options['website']),
                'choice_attr' => function ($dir, $key, $value) {
                    return ['data-background' => strtolower($dir)];
                },
                'attr' => [
                    'group' => 'col-md-2 markers-select',
                    'class' => 'select-icons',
                ],
            ]);

            $builder->add('hide', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Cacher le point', [], 'admin'),
                'attr' => ['group' => 'col-md-2 d-flex align-items-end', 'class' => 'w-100 mb-3'],
            ]);

            if ($map && $map->isDisplayFilters()) {
                $builder->add('categories', EntityType::class, [
                    'label' => $this->translator->trans('Catégories', [], 'admin'),
                    'required' => false,
                    'display' => 'search',
                    'attr' => [
                        'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    ],
                    'class' => Category::class,
                    'choice_label' => function ($entity) {
                        return strip_tags($entity->getAdminName());
                    },
                    'multiple' => true,
                ]);
            }

            if ($map && $map->isCountriesGeometry()) {
                $builder->add('countries', Type\CountryType::class, [
                    'label' => $this->translator->trans('Pays', [], 'admin'),
                    'required' => false,
                    'multiple' => true,
                    'display' => 'search',
                    'attr' => [
                        'group' => 'col-md-6',
                        'data-placeholder' => $this->translator->trans('Sélectionnez des pays', [], 'admin'),
                    ],
                ]);
            }

            if ($map && $map->isDepartmentsGeometry()) {
                $builder->add('departments', WidgetType\DepartmentType::class, [
                    'label' => $this->translator->trans('Départements', [], 'admin'),
                    'required' => false,
                    'multiple' => true,
                    'attr' => [
                        'group' => 'col-md-6',
                        'data-placeholder' => $this->translator->trans('Sélectionnez des départements', [], 'admin'),
                    ],
                ]);
            }

            $builder->add('address', AddressType::class, ['label' => false]);

            $builder->add('phones', CollectionType::class, [
                'label' => false,
                'entry_type' => PhoneType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => [
                    'attr' => [
                        'class' => 'phone',
                        'icon' => 'fal phone',
                        'group' => 'col-md-3',
                        'caption' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
                        'button' => $this->translator->trans('Ajouter un numéro', [], 'admin'),
                    ],
                    'locale' => false,
                    'entitled' => false,
                    'type' => false,
                    'zones' => false,
                    'website' => $website,
                ],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'data_config' => true,
                'title_force' => false,
                'fields' => [
                    'title',
                    'body',
                    'targetLink' => 'col-md-12',
                    'targetPage' => 'col-md-4',
                    'targetLabel' => 'col-md-4',
                    'targetStyle' => 'col-md-4',
                    'newTab' => 'col-md-4',
                    'externalLink' => 'col-md-4',
                ],
            ]);

            if ($map && $map->isJsonGeometry()) {
                $builder->add('geoJson', WidgetType\MediaRelationType::class, [
                    'onlyMedia' => true,
                    'data_class' => PointGeoJson::class,
                ]);
            }

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'entry_options' => ['onlyMedia' => true],
                'data_config' => true,
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    /**
     * Get markers choices.
     */
    private function getMarkers(Website $website): array
    {
        $folder = $this->entityManager->getRepository(Folder::class)->findOneBy([
            'website' => $website,
            'slug' => 'map',
        ]);

        $markers = [];
        if ($folder) {
            foreach ($folder->getMedias() as $media) {
                $markers[$media->getFilename()] = '/uploads/'.$website->getUploadDirname().'/'.$media->getFilename();
            }
        }

        return $markers;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Point::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
