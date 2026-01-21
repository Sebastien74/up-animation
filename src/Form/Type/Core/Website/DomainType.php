<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Core\Configuration;
use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DomainType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DomainType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * DomainType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $website = $options['website_edit'] instanceof Website ? $options['website_edit'] : $options['website'];
        $configuration = $website->getConfiguration();
        $locales = $this->getLocales($configuration);

        if (!empty($locales) && count($locales) > 1) {
            $builder->add('locale', Type\ChoiceType::class, [
                'label' => false,
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'choices' => $locales,
                'choice_translation_domain' => false,
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-12 mb-0',
                ],
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

        $builder->add('name', Type\TextType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le code URL (Sans protocole)', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('asDefault', Type\CheckboxType::class, [
            'label' => false,
            'display' => 'switch',
            'uniq_id' => false,
        ]);
    }

    /**
     * Get locales.
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
            'data_class' => Domain::class,
            'website' => null,
            'website_edit' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
