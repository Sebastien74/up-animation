<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Map;

use App\Entity\Module\Map\Map;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MapType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MapType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * MapType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface  $coreLocator,
        private readonly TokenStorageInterface $tokenStorage
    )
    {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug-internal' => $this->isInternalUser]);

        if (!$isNew) {

            $builder->add('latitude', Type\TextType::class, [
                'label' => $this->translator->trans('Latitude de centrage', [], 'admin'),
                'attr' => ['class' => 'col-12 latitude',
                    'placeholder' => $this->translator->trans('Saisissez une latitude', [],
                        'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'constraints' => [new Assert\NotBlank()],
            ]);

            $builder->add('longitude', Type\TextType::class, [
                'label' => $this->translator->trans('Longitude de centrage', [], 'admin'),
                'attr' => ['class' => 'col-12 longitude',
                    'placeholder' => $this->translator->trans('Saisissez une longitude', [],
                        'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'constraints' => [new Assert\NotBlank()],
            ]);

            if ($this->isInternalUser) {

                $builder->add('height', Type\IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('Hauteur (pixels)', [], 'admin'),
                    'attr' => ['data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-8 col-lg-2'],
                ]);

                $builder->add('zoom', Type\IntegerType::class, [
                    'label' => $this->translator->trans('Zoom', [], 'admin'),
                    'attr' => ['data-config' => true, 'min' => 1, 'max' => 16],
                    'row_attr' => ['class' => 'col-12 col-md-8 col-lg-2'],
                ]);

                $builder->add('minZoom', Type\IntegerType::class, [
                    'label' => $this->translator->trans('Zoom minimum', [], 'admin'),
                    'attr' => ['data-config' => true, 'min' => 1, 'max' => 16],
                    'row_attr' => ['class' => 'col-12 col-md-8 col-lg-2'],
                ]);

                $builder->add('maxZoom', Type\IntegerType::class, [
                    'label' => $this->translator->trans('Zoom maximum', [], 'admin'),
                    'attr' => ['data-config' => true, 'min' => 1, 'max' => 25],
                    'row_attr' => ['class' => 'col-12 col-md-8 col-lg-2'],
                ]);

                $builder->add('layer', Type\UrlType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('Template de la carte', [], 'admin'),
                    'attr' => ['placeholder' => $this->translator->trans('Saisissez une URL', [],
                        'admin'),
                        'data-config' => true
                    ],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
                    'help' => '<a href="https://leaflet-extras.github.io/leaflet-providers/preview/" target="_blank">'.$this->translator->trans('Trouver un templates', [], 'admin').'</a>',
                ]);

                $builder->add('autoCenter', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Centrer automatiquement la carte', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('forceZoom', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Forcer le zoom (Si centré auto)', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('displayFilters', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Afficher les filtres', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('multiFilters', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Choix multiple des filtres', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('markerClusters', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Activer les groupes de points', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('displayPointsList', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Afficher la liste des points', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('asDefault', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Carte principale', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('popupHover', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Afficher la popup au hover', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('countriesGeometry', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Géomérties des pays', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('departmentsGeometry', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Géomérties des départements', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);

                $builder->add('jsonGeometry', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'app',
                    'label' => $this->translator->trans('Activer les fichiers de Géomérties', [], 'admin'),
                    'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                ]);
            }
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Map::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
