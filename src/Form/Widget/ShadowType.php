<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ShadowType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ShadowType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?Website $website;

    /**
     * ShadowType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->website = $this->coreLocator->website()->entity;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'label' => $this->translator->trans('Ombre', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'display' => 'search',
            'attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'group' => 'col-md-4',
                ]);
            },
            'choices' => $this->getColors(),
        ]);
    }

    /**
     * Get WebsiteModel background colors.
     */
    private function getColors(): array
    {
        $haveWhite = false;
        $colors = $this->website->getConfiguration()->getColors();
        $labels = [
            'white' => $this->translator->trans('Blanche', [], 'admin'),
            'primary' => $this->translator->trans('Principale', [], 'admin'),
            'secondary' => $this->translator->trans('Secondaire', [], 'admin'),
            'gold' => $this->translator->trans('Dorée', [], 'admin'),
            'black' => $this->translator->trans('Noire', [], 'admin'),
            'inactif' => $this->translator->trans('Grise', [], 'admin'),
            'muted' => $this->translator->trans('Grise', [], 'admin'),
            'lighten' => $this->translator->trans('Grise claire', [], 'admin'),
            'info-light' => $this->translator->trans('Bleue claire', [], 'admin'),
        ];

        $choicesColors = [];
        foreach ($colors as $color) {
            $matches = explode(' ', $color->getAdminName());
            $colorSlug = str_replace('bg-', '', $color->getSlug());
            $colorName = !empty($labels[$colorSlug]) ? $labels[$colorSlug]
                : (!empty($labels[end($matches)]) ? $labels[end($matches)] : end($matches));
            if ('white' === $colorSlug) {
                $haveWhite = true;
            }
            if ('background' === $color->getCategory() && $color->isActive()) {
                $choicesColors[ucfirst($colorName)] = $colorSlug;
            }
        }

        if (!$haveWhite) {
            $choicesColors['white'] = 'white';
        }

        $sides = [
            'top' => $this->translator->trans('En haut', [], 'admin'),
            'bottom' => $this->translator->trans('En bas', [], 'admin'),
            'left' => $this->translator->trans('À gauche', [], 'admin'),
            'right' => $this->translator->trans('À droite', [], 'admin'),
        ];

        $choices = [];
        foreach ($choicesColors as $colorLabel => $colorSlug) {
            foreach ($sides as $side => $sideLabel) {
                $choices[$colorLabel.' ('.$sideLabel.')'] = 'shadow-'.$side.'-'.$colorSlug;
            }
        }

        ksort($choices);

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}