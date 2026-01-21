<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LaxEffectType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LaxEffectType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * LaxEffectType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'multiple' => true,
            'display' => 'search',
            'choices' => $this->getLaxEffects(),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'label' => $this->translator->trans('Effets Lax', [], 'admin'),
        ]);
    }

    /**
     * Get effects.
     */
    private function getLaxEffects(): array
    {
        $choices = [
            'linger' => 'linger',
            'lazy' => 'lazy',
            'eager' => 'eager',
            'slalom' => 'slalom',
            'crazy' => 'crazy',
            'spin' => 'spin',
            'spinRev' => 'spinRev',
            'spinIn' => 'spinIn',
            'spinOut' => 'spinOut',
            'blurInOut' => 'blurInOut',
            'blurIn' => 'blurIn',
            'blurOut' => 'blurOut',
            'fadeInOut' => 'fadeInOut',
            'fadeIn' => 'fadeIn',
            'fadeOut' => 'fadeOut',
            'driftLeft' => 'driftLeft',
            'driftRight' => 'driftRight',
            'leftToRight' => 'leftToRight',
            'rightToLeft' => 'rightToLeft',
            'zoomInOut' => 'zoomInOut',
            'zoomIn' => 'zoomIn',
            'zoomOut' => 'zoomOut',
            'swing' => 'swing',
            'speedy' => 'speedy',
        ];

        ksort($choices);

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
