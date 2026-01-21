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
        $builder->add('name', Type\TextType::class, [
            'label' => $this->translator->trans('Raison sociale', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-6',
                'placeholder' => $this->translator->trans('Saisissez une raison sociale', [], 'admin'),
            ],
        ]);

        $builder->add('latitude', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Latitude', [], 'admin'),
            'attr' => [
                'group' => 'col-md-3',
                'class' => 'latitude',
                'placeholder' => $this->translator->trans('Saisissez une latitude', [], 'admin'),
            ],
        ]);

        $builder->add('longitude', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Longitude', [], 'admin'),
            'attr' => [
                'group' => 'col-md-3',
                'class' => 'longitude',
                'placeholder' => $this->translator->trans('Saisissez une longitude', [], 'admin'),
            ],
        ]);

        $builder->add('zoom', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Zoom', [], 'admin'),
            'attr' => ['group' => 'col-md-4', 'data-config' => true, 'min' => 1, 'max' => 16],
        ]);

        $builder->add('minZoom', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Zoom minimum', [], 'admin'),
            'attr' => ['group' => 'col-md-4', 'data-config' => true, 'min' => 1, 'max' => 16],
        ]);

        $builder->add('maxZoom', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Zoom maximum', [], 'admin'),
            'attr' => ['group' => 'col-md-4', 'data-config' => true, 'min' => 1, 'max' => 25],
        ]);

        $builder->add('address', Type\TextType::class, [
            'label' => $this->translator->trans('Adresse', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-9',
                'class' => 'address',
                'placeholder' => $this->translator->trans('Saisissez une adresse', [], 'admin'),
            ],
        ]);

        $builder->add('city', Type\TextType::class, [
            'label' => $this->translator->trans('Ville', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-3',
                'class' => 'city',
                'placeholder' => $this->translator->trans('Saisissez une ville', [], 'admin'),
            ],
        ]);

        $builder->add('zipCode', Type\TextType::class, [
            'label' => $this->translator->trans('Code postal', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-3',
                'class' => 'zip-code',
                'placeholder' => $this->translator->trans('Saisissez un code postal', [], 'admin'),
            ],
            'constraints' => [new ZipCode()],
        ]);

        $builder->add('department', Type\TextType::class, [
            'label' => $this->translator->trans('Département', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-3',
                'class' => 'department',
                'placeholder' => $this->translator->trans('Saisissez une département', [], 'admin'),
            ],
        ]);

        $builder->add('region', Type\TextType::class, [
            'label' => $this->translator->trans('Région', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-3',
                'class' => 'region',
                'placeholder' => $this->translator->trans('Saisissez une région', [], 'admin'),
            ],
        ]);

        $builder->add('country', Type\CountryType::class, [
            'label' => $this->translator->trans('Pays', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Sélectionnez un pays', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'country'],
        ]);

        $builder->add('googleMapUrl', Type\UrlType::class, [
            'label' => $this->translator->trans('Google map URL', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-6',
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
            ],
        ]);

        $builder->add('googleMapDirectionUrl', Type\UrlType::class, [
            'label' => $this->translator->trans('Google map itinéraire URL', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-6',
                'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
            ],
        ]);

        $builder->add('phones', CollectionType::class, [
            'label' => false,
            'entry_type' => AddressPhoneType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => [
                    'class' => 'address-phone',
                    'icon' => 'fal phone',
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
                'attr' => [
                    'group' => 'col-md-3',
                    'class' => 'address-email',
                    'icon' => 'fal at',
                    'caption' => $this->translator->trans('E-mails', [], 'admin'),
                    'button' => $this->translator->trans('Ajouter un e-mail', [], 'admin'),
                ],
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
            'prototypePosition' => true,
            'translation_domain' => 'admin',
        ]);
    }
}
