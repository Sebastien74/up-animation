<?php

declare(strict_types=1);

namespace App\Form\Type\Information;

use App\Entity\Core\Configuration;
use App\Entity\Information\Legal;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LegalType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LegalType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * LegalType constructor.
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

        $builder->add('companyName', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Raison sociale', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une raison sociale', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('companyRepresentativeName', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Nom du représentant légal de l’entreprise', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('capital', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Capital', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un capital', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('vatNumber', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Numéro de TVA', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('siretNumber', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Numéro de SIRET', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('commercialRegisterNumber', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Numéro registre du commerce', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('companyAddress', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Adresse', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une adresse', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('managerName', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Nom du responsable de la publication', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('managerEmail', Type\EmailType::class, [
            'required' => false,
            'label' => $this->translator->trans('E-mail du responsable', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('webmasterName', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Nom du Webmaster', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('webmasterEmail', Type\EmailType::class, [
            'required' => false,
            'label' => $this->translator->trans('E-mail du Webmaster', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('hostName', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans("Nom de l'hébergeur", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('hostAddress', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans("Adresse de l'hébergeur", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une adresse', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('protectionOfficerName', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Nom du délégué à la protection des données', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('protectionOfficerEmail', Type\EmailType::class, [
            'required' => false,
            'label' => $this->translator->trans('E-mail du délégué', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('protectionOfficerAddress', Type\EmailType::class, [
            'required' => false,
            'label' => $this->translator->trans('Adresse du délégué', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une adresse', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('hostAddress', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans("Adresse de l'hébergeur", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une adresse', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        if ($multiLocales) {
            $builder->add('locale', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Langue', [], 'admin'),
                'choices' => $locales,
                'choice_translation_domain' => false,
                'attr' => ['class' => 'select-icons', 'group' => 'col-md-4'],
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
            'data_class' => Legal::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
