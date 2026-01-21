<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ButtonColorType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ButtonColorType extends AbstractType
{
    private const bool GRADIENTS_FIRST = true;

    private TranslatorInterface $translator;
    private ?WebsiteModel $website;
    private object $customModules;
    private array $colors = [];

    /**
     * ButtonColorType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator) {
        $this->translator = $this->coreLocator->translator();
        $this->website = $this->coreLocator->website();
        $this->customModules = $this->website->configuration->customModules;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Style du lien', [], 'admin'),
            'required' => false,
            'linkColors' => $this->customModules->linkColors,
            'cta' => $this->customModules->cta,
            'ctaColors' => $this->customModules->ctaColors,
            'gradientColors' => $this->customModules->gradientColors,
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'class' => 'select-icons',
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'group' => 'col-md-4',
                    'data-config' => false,
                ]);
            },
            'choice_attr' => function ($color) {
                return [
                    'data-class' => str_contains($color, 'outline') ? 'square-outline' : 'square',
                    'data-color' => $this->colors[$color],
                ];
            },
        ]);

        $resolver->setNormalizer('choices', function (OptionsResolver $options, $value) {
            return $this->getColors($options);
        });
    }

    /**
     * Get WebsiteModel buttons colors.
     */
    private function getColors(OptionsResolver $options): array
    {
        $linkColors = $options['linkColors'];
        $colors = $this->website->entity->getConfiguration()->getColors();
        $choices = $defaultChoices = [];
        $choices[$this->translator->trans('Séléctionnez', [], 'admin')] = '';
        $choices[$this->translator->trans('Lien classique', [], 'admin')] = 'link';
        $this->colors[''] = '';
        $this->colors['link'] = '#ffffff';

        foreach ($colors as $color) {
            if ('button' === $color->getCategory() && $color->isActive()) {
                if (!self::GRADIENTS_FIRST) {
                    $choices[$this->translator->trans($color->getAdminName())] = $color->getSlug();
                }
                $defaultChoices[$this->translator->trans($color->getAdminName())] = $color->getSlug();
                $this->colors[$color->getSlug()] = $color->getColor();
                if (!str_contains($color->getSlug(), 'outline') && str_contains($color->getSlug(), 'btn')) {
                    $ctaValue = str_replace(['btn'], ['cta'], $color->getSlug());
                    $this->colors[$ctaValue] = $color->getColor();
                    $gradientValue = str_replace(['btn'], ['btn-gradient btn-gradient'], $color->getSlug());
                    $this->colors[$gradientValue] = $color->getColor();
                    $linkValue = str_replace(['btn'], ['text'], $color->getSlug());
                    $this->colors[$linkValue] = $color->getColor();
                }
            }
        }

        if ($options['gradientColors']) {
            foreach ($defaultChoices as $label => $value) {
                if (!str_contains($value, 'outline') && str_contains($value, 'btn') && !str_contains($value, 'white')) {
                    $label = str_replace(['Bouton', 'Button'], ['Bouton dégradé'], $label);
                    $value = str_replace(['btn'], ['btn-gradient btn-gradient'], $value);
                    $choices[$label] = $value;
                }
            }
        }

        if (self::GRADIENTS_FIRST) {
            $choices = array_merge($choices, $defaultChoices);
        }

        if ($linkColors) {
            foreach ($defaultChoices as $label => $value) {
                if (!str_contains($value, 'outline') && str_contains($value, 'btn')) {
                    $label = str_replace(['Bouton', 'Button'], ['Lien'], $label);
                    $value = str_replace(['btn'], ['text'], $value);
                    $choices[$label] = $value;
                }
            }
        }

        if ($options['ctaColors']) {
            foreach ($defaultChoices as $label => $value) {
                if (!str_contains($value, 'outline') && str_contains($value, 'btn')) {
                    $label = str_replace(['Bouton', 'Button'], ['CTA'], $label);
                    $value = str_replace(['btn'], ['cta'], $value);
                    $choices[$label] = $value;
                }
            }
        } elseif ($options['cta']) {
            $this->colors['cta'] = '';
            $choices[$this->translator->trans('CTA', [], 'admin')] = 'cta';
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
