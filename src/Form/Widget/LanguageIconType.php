<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LanguageIconType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LanguageIconType extends AbstractType
{
    private const array LANGUAGES = ['fr', 'fr_ch', 'en', 'es', 'it', 'de', 'fr_be', 'nl_be', 'pt', 'nl', 'zh', 'ja'];
    private TranslatorInterface $translator;

    /**
     * LanguageIconType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Langue', [], 'admin'),
            'multiple' => false,
            'display' => 'select-flags',
            'choices' => $this->getLanguages(),
            'choice_attr' => function ($iso, $key, $value) {
                return [
                    'data-image' => '/medias/icons/flags/'.strtolower($iso).'.svg',
                    'data-class' => 'flag mt-min',
                    'data-text' => true,
                    'data-height' => 14,
                    'data-width' => 19,
                ];
            },
        ]);
    }

    /**
     * Get App languages.
     */
    private function getLanguages(): array
    {
        $locales = [];
        foreach (self::LANGUAGES as $locale) {
            try {
                $name = !in_array(Languages::getName($locale), $locales) ? Languages::getName($locale) : Languages::getName($locale).' ('.strtoupper($locale).')';
                $locales[$locale] = $name;
            } catch (\Exception $exception) {
            }
        }

        $languages = [];
        foreach (self::LANGUAGES as $iso) {
            if (!empty($locales[$iso])) {
                $matches = explode('_', $iso);
                $label = 1 === count($matches) ? Languages::getName($iso) : Languages::getName($iso).' ('.Countries::getName(strtoupper($matches[1])).')';
                $languages[$label] = $iso;
            }
        }

        return $languages;
    }

    public function getParent(): ?string
    {
        return Type\ChoiceType::class;
    }
}
