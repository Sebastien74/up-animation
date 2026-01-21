<?php

declare(strict_types=1);

namespace App\Form\Type\Information;

use App\Entity\Core\Configuration;
use App\Entity\Information\Email;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EmailType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * EmailType constructor.
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

        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('E-mail', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('entitled', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Intitulé', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
            ],
        ]);

        if ($multiLocales) {
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

        $builder->add('zones', Type\ChoiceType::class, [
            'label' => $this->translator->trans("Zones d'affichage", [], 'admin'),
            'display' => 'search',
            'multiple' => true,
            'required' => false,
            'choices' => [
                $this->translator->trans('Page de contact', [], 'admin') => 'contact',
                $this->translator->trans('Navigation', [], 'admin') => 'header',
                $this->translator->trans('Pied de page', [], 'admin') => 'footer',
                $this->translator->trans('E-mail', [], 'admin') => 'email',
                $this->translator->trans('Page de maintenance', [], 'admin') => 'maintenance'
            ],
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez une zone', [], 'admin'),
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
            'data_class' => Email::class,
            'website' => null,
            'custom_widget' => true,
            'prototypePosition' => true,
            'translation_domain' => 'admin',
        ]);
    }
}
