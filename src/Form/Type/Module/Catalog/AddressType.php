<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Core\Configuration;
use App\Entity\Information\Address;
use App\Form\Type\Information\AddressEmailType;
use App\Form\Type\Information\AddressPhoneType;
use App\Form\Validator\ZipCode;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AddressType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AddressType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AddressType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['map_fields']) {

            $builder->add('name', Type\TextType::class, [
                'label' => $this->translator->trans('Raison sociale', [], 'admin'),
                'required' => false,
                'attr' => ['placeholder' => $this->translator->trans('Saisissez une raison sociale', [],
                    'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            ]);

            $builder->add('latitude', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Latitude', [], 'admin'),
                'attr' => ['class' => 'col-12 latitude',
                    'placeholder' => $this->translator->trans('Saisissez une latitude', [],
                        'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('longitude', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Longitude', [], 'admin'),
                'attr' => ['class' => 'col-12 longitude',
                    'placeholder' => $this->translator->trans('Saisissez une longitude', [],
                        'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('zoom', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Zoom', [], 'admin'),
                'attr' => ['data-config' => true, 'min' => 1, 'max' => 16],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('minZoom', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Zoom minimum', [], 'admin'),
                'attr' => ['data-config' => true, 'min' => 1, 'max' => 16],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('maxZoom', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Zoom maximum', [], 'admin'),
                'attr' => ['data-config' => true, 'min' => 1, 'max' => 25],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);
        }

        $builder->add('address', Type\TextType::class, [
            'label' => $this->translator->trans('Adresse', [], 'admin'),
            'required' => false,
            'attr' => ['class' => 'col-12 address',
                'placeholder' => $this->translator->trans('Saisissez une adresse', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-9'],
        ]);

        $builder->add('city', Type\TextType::class, [
            'label' => $this->translator->trans('Ville', [], 'admin'),
            'required' => false,
            'attr' => ['class' => 'col-12 city',
                'placeholder' => $this->translator->trans('Saisissez une ville', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $builder->add('zipCode', Type\TextType::class, [
            'label' => $this->translator->trans('Code postal', [], 'admin'),
            'required' => false,
            'attr' => ['class' => 'col-12 zip-code',
                'placeholder' => $this->translator->trans('Saisissez un code postal', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => [new ZipCode()],
        ]);

        $builder->add('department', Type\TextType::class, [
            'label' => $this->translator->trans('Département', [], 'admin'),
            'required' => false,
            'attr' => ['class' => 'col-12 department',
                'placeholder' => $this->translator->trans('Saisissez une département', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $builder->add('region', Type\TextType::class, [
            'label' => $this->translator->trans('Région', [], 'admin'),
            'required' => false,
            'attr' => ['class' => 'col-12 region',
                'placeholder' => $this->translator->trans('Saisissez une région', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $builder->add('country', Type\CountryType::class, [
            'label' => $this->translator->trans('Pays', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Sélectionnez un pays', [], 'admin'),
            'preferred_choices' => ['FR', 'CH'],
            'attr' => ['class' => 'col-12 country'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        if ($options['google_fields']) {

            $builder->add('googleMapUrl', Type\UrlType::class, [
                'label' => $this->translator->trans('Google map URL', [], 'admin'),
                'required' => false,
                'attr' => ['placeholder' => $this->translator->trans('Saisissez une URL', [],
                    'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            ]);

            $builder->add('googleMapDirectionUrl', Type\UrlType::class, [
                'label' => $this->translator->trans('Google map itinéraire URL', [], 'admin'),
                'required' => false,
                'attr' => ['placeholder' => $this->translator->trans('Saisissez une URL', [],
                    'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            ]);
        }

        $builder->add('phones', CollectionType::class, [
            'label' => false,
            'entry_type' => AddressPhoneType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => [
                    'class' => 'col-12 address-phone',
                    'icon' => 'phone',
                    'caption' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
                    'button' => $this->translator->trans('Ajouter un numéro', [], 'admin'),
                ],
                'website' => $options['website'],
            ],
        ]);

        $builder->add('emails', CollectionType::class, [
            'label' => false,
            'entry_type' => AddressEmailType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => ['class' => 'col-12 address-email',
                    'icon' => 'at',
                    'caption' => $this->translator->trans('E-mails', [],
                        'admin'),
                    'button' => $this->translator->trans('Ajouter un e-mail', [], 'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'website' => $options['website'],
            ],
        ]);
    }

    /**
     * Get WebsiteModel locales.
     */
    private function getLocales(Configuration $configuration): array
    {
        $defaultLocale = $configuration->getLocale();
        $name = empty($locales[Languages::getName($defaultLocale)]) ? Languages::getName($defaultLocale) : Languages::getName($defaultLocale).' ('.strtoupper($defaultLocale).')';
        $locales[$name] = $defaultLocale;
        foreach ($configuration->getLocales() as $locale) {
            $name = empty($locales[Languages::getName($locale)]) ? Languages::getName($locale) : Languages::getName($locale).' ('.strtoupper($locale).')';
            $locales[$name] = $locale;
        }

        return $locales;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'website' => null,
            'map_fields' => false,
            'google_fields' => false,
            'prototypePosition' => true,
            'translation_domain' => 'admin',
        ]);
    }
}
