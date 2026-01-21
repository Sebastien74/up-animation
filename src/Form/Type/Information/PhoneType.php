<?php

declare(strict_types=1);

namespace App\Form\Type\Information;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Information\Phone;
use App\Form\Validator;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PhoneType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PhoneType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PhoneType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Website $website */
        $website = $options['website'];
        $configuration = $website->getConfiguration();
        $locales = $this->getLocales($configuration);
        $multiLocales = count($locales) > 1;

        $builder->add('number', Type\TextType::class, [
            'label' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Validator\Phone(),
            ],
        ]);

        $builder->add('tagNumber', Type\TextType::class, [
            'label' => $this->translator->trans('Numéro de téléphone (href)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Validator\Phone(),
            ],
        ]);

        if ($options['entitled']) {
            $builder->add('entitled', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Intitulé', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
                ],
            ]);
        }

        if ($options['type']) {
            $builder->add('type', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Type', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Fixe', [], 'admin') => 'fixe',
                    $this->translator->trans('Portable', [], 'admin') => 'mobile',
                    $this->translator->trans('Fax', [], 'admin') => 'fax',
                ],
                'attr' => [
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'constraints' => [new Assert\NotBlank()],
            ]);
        }

        if ($multiLocales && $options['locale']) {
            $builder->add('locale', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Langue', [], 'admin'),
                'choices' => $locales,
                'choice_translation_domain' => false,
                'attr' => ['class' => 'select-icons'],
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

        if ($options['zones']) {

            $choices[$this->translator->trans('Page de contact', [], 'admin')] = 'contact';
            $choices[$this->translator->trans('Navigation', [], 'admin')] = 'header';
            $choices[$this->translator->trans('Pied de page', [], 'admin')] = 'footer';
            $choices[$this->translator->trans('E-mail', [], 'admin')] = 'email';
            $choices[$this->translator->trans('Page de maintenance', [], 'admin')] = 'maintenance';
            if ($website->getSeoConfiguration()->isMicroData()) {
                $choices[$this->translator->trans('Micro data', [], 'admin')] = 'microdata';
            }

            $builder->add('zones', Type\ChoiceType::class, [
                'label' => $this->translator->trans("Zones d'affichage", [], 'admin'),
                'display' => 'search',
                'multiple' => true,
                'required' => false,
                'choices' => $choices,
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez une zone', [], 'admin'),
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
            'data_class' => Phone::class,
            'website' => null,
            'locale' => true,
            'entitled' => true,
            'type' => true,
            'zones' => true,
            'prototypePosition' => true,
            'translation_domain' => 'admin',
        ]);
    }
}
