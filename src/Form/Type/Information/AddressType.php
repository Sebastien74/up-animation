<?php

declare(strict_types=1);

namespace App\Form\Type\Information;

use App\Entity\Core\Configuration;
use App\Entity\Information\Address;
use App\Form\Validator;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
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
        /** @var Configuration $configuration */
        $configuration = $options['website']->getConfiguration();
        $locales = $this->getLocales($configuration);
        $multiLocales = count($locales) > 1;
        $labels = $options['label_fields'];

        if (!in_array('name', $options['excludes_fields'])) {
            $builder->add('name', Type\TextType::class, [
                'label' => !empty($labels['name']) ? $labels['name'] : $this->translator->trans('Raison sociale', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => $multiLocales ? 'col-md-9' : 'col-12',
                    'placeholder' => $this->translator->trans('Saisissez une raison sociale', [], 'admin'),
                ],
            ]);
        }

        if ($multiLocales) {
            $builder->add('locale', Type\ChoiceType::class, [
                'label' => !empty($labels['locale']) ? $labels['locale'] : $this->translator->trans('Langue', [], 'admin'),
                'choices' => $locales,
                'choice_translation_domain' => false,
                'attr' => ['class' => 'select-icons', 'group' => 'col-md-3'],
                'choice_attr' => function ($iso, $key, $value) {
                    return [
                        'data-image' => '/medias/icons/flags/'.strtolower($iso).'.svg',
                        'data-class' => 'flag mt-min',
                        'data-text' => true,
                        'data-height' => 14,
                        'data-width' => 19,
                    ];
                },
                'constraints' => [new Assert\NotBlank()],
            ]);
        } else {
            $builder->add('locale', Type\HiddenType::class, [
                'data' => $configuration->getLocale(),
            ]);
        }

        if (!in_array('zones', $options['excludes_fields'])) {
            $builder->add('zones', Type\ChoiceType::class, [
                'label' => !empty($labels['zones']) ? $labels['zones'] : $this->translator->trans("Zones d'affichage", [], 'admin'),
                'display' => 'search',
                'multiple' => true,
                'required' => false,
                'choices' => [
                    $this->translator->trans('Page de contact', [], 'admin') => 'contact',
                    $this->translator->trans('Navigation', [], 'admin') => 'header',
                    $this->translator->trans('Pied de page', [], 'admin') => 'footer',
                    $this->translator->trans('E-mail', [], 'admin') => 'email',
                    $this->translator->trans('Page de maintenance', [], 'admin') => 'maintenance',
                ],
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez une zone', [], 'admin'),
                ],
            ]);
        }

        if (!in_array('address', $options['excludes_fields'])) {
            $builder->add('address', Type\TextType::class, [
                'label' => !empty($labels['address']) ? $labels['address'] : $this->translator->trans('Adresse', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => 'col-md-9',
                    'placeholder' => $this->translator->trans('Saisissez une adresse', [], 'admin'),
                ],
            ]);
        }

        if (!in_array('zipCode', $options['excludes_fields'])) {
            $builder->add('zipCode', Type\TextType::class, [
                'label' => !empty($labels['zipCode']) ? $labels['zipCode'] : $this->translator->trans('Code postal', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Saisissez un code postal', [], 'admin'),
                ],
                'constraints' => [new Validator\ZipCode()],
            ]);
        }

        if (!in_array('city', $options['excludes_fields'])) {
            $builder->add('city', Type\TextType::class, [
                'label' => !empty($labels['city']) ? $labels['city'] : $this->translator->trans('Ville', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Saisissez une ville', [], 'admin'),
                ],
            ]);
        }

        if (!in_array('department', $options['excludes_fields'])) {
            $builder->add('department', Type\TextType::class, [
                'label' => !empty($labels['department']) ? $labels['department'] : $this->translator->trans('Département', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Saisissez une département', [], 'admin'),
                ],
            ]);
        }

        if (!in_array('region', $options['excludes_fields'])) {
            $builder->add('region', Type\TextType::class, [
                'label' => !empty($labels['region']) ? $labels['region'] : $this->translator->trans('Région', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Saisissez une région', [], 'admin'),
                ],
            ]);
        }

        if (!in_array('country', $options['excludes_fields'])) {
            $builder->add('country', Type\CountryType::class, [
                'label' => !empty($labels['country']) ? $labels['country'] : $this->translator->trans('Pays', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'placeholder' => $this->translator->trans('Sélectionnez un pays', [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
            ]);
        }

        if (!in_array('googleMapUrl', $options['excludes_fields'])) {
            $builder->add('googleMapUrl', Type\UrlType::class, [
                'label' => !empty($labels['googleMapUrl']) ? $labels['googleMapUrl'] : $this->translator->trans("Plan d'accès", [], 'admin'),
                'required' => false,
                'display' => 'search',
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
                ],
                'constraints' => [new Assert\Url()],
            ]);
        }

        if (!in_array('phones', $options['excludes_fields'])) {
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
        }

        if (!in_array('emails', $options['excludes_fields'])) {
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

        if (!in_array('schedule', $options['excludes_fields'])) {
            $builder->add('schedule', Type\TextareaType::class, [
                'required' => false,
                'label' => !empty($labels['schedule']) ? $labels['schedule'] : $this->translator->trans('Horaires', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez vos horaires', [], 'admin'),
                ],
            ]);
        }
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
            'label_fields' => [],
            'excludes_fields' => [],
            'translation_domain' => 'admin',
        ]);
    }
}
