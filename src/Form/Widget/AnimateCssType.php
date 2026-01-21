<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AnimateCssType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AnimateCssType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AnimateCssType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'display' => 'search',
            'choices' => $this->getAosEffects(),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'label' => $this->translator->trans('Ou effet Animate CSS', [], 'admin'),
        ]);
    }

    /**
     * Get effects.
     */
    private function getAosEffects(): array
    {
        $animationsGroups = [
            'Attention seekers' => [
                'bounce' => 'bounce',
                'flash' => 'flash',
                'pulse' => 'pulse',
                'rubberBand' => 'rubberBand',
                'shakeX' => 'shakeX',
                'shakeY' => 'shakeY',
                'headShake' => 'headShake',
                'swing' => 'swing',
                'tada' => 'tada',
                'wobble' => 'wobble',
                'jello' => 'jello',
                'heartBeat' => 'heartBeat',
            ],
            'Back entrances' => [
                'backInDown' => 'backInDown',
                'backInLeft' => 'backInLeft',
                'backInRight' => 'backInRight',
                'backInUp' => 'backInUp',
            ],
            'Back exits' => [
                'backOutDown' => 'backOutDown',
                'backOutLeft' => 'backOutLeft',
                'backOutRight' => 'backOutRight',
                'backOutUp' => 'backOutUp',
            ],
            'Bouncing entrances' => [
                'bounceIn' => 'bounceIn',
                'bounceInDown' => 'bounceInDown',
                'bounceInLeft' => 'bounceInLeft',
                'bounceInRight' => 'bounceInRight',
                'bounceInUp' => 'bounceInUp',
            ],
            'Bouncing exits' => [
                'bounceOut' => 'bounceOut',
                'bounceOutDown' => 'bounceOutDown',
                'bounceOutLeft' => 'bounceOutLeft',
                'bounceOutUp' => 'bounceOutUp',
                'bounceOutRight' => 'bounceOutRight',
            ],
            'Fading entrances' => [
                'fadeIn' => 'fadeIn',
                'fadeInDown' => 'fadeInDown',
                'fadeInDownBig' => 'fadeInDownBig',
                'fadeInLeft' => 'fadeInLeft',
                'fadeInLeftBig' => 'fadeInLeftBig',
                'fadeInRight' => 'fadeInRight',
                'fadeInRightBig' => 'fadeInRightBig',
                'fadeInUp' => 'fadeInUp',
                'fadeInUpBig' => 'fadeInUpBig',
                'fadeInTopLeft' => 'fadeInTopLeft',
                'fadeInTopRight' => 'fadeInTopRight',
                'fadeInBottomLeft' => 'fadeInBottomLeft',
                'fadeInBottomRight' => 'fadeInBottomRight',
            ],
            'Fading exits' => [
                'fadeOut' => 'fadeOut',
                'fadeOutDown' => 'fadeOutDown',
                'fadeOutDownBig' => 'fadeOutDownBig',
                'fadeOutLeft' => 'fadeOutLeft',
                'fadeOutLeftBig' => 'fadeOutLeftBig',
                'fadeOutRight' => 'fadeOutRight',
                'fadeOutRightBig' => 'fadeOutRightBig',
                'fadeOutUp' => 'fadeOutUp',
                'fadeOutUpBig' => 'fadeOutUpBig',
                'fadeOutTopLeft' => 'fadeOutTopLeft',
                'fadeOutTopRight' => 'fadeOutTopRight',
                'fadeOutBottomRight' => 'fadeOutBottomRight',
                'fadeOutBottomLeft' => 'fadeOutBottomLeft',
            ],
            'Flippers' => [
                'flip' => 'flip',
                'flipInX' => 'flipInX',
                'flipInY' => 'flipInY',
                'flipOutX' => 'flipOutX',
                'flipOutY' => 'flipOutY',
            ],
            'Lightspeed' => [
                'lightSpeedInRight' => 'lightSpeedInRight',
                'lightSpeedInLeft' => 'lightSpeedInLeft',
                'lightSpeedOutRight' => 'lightSpeedOutRight',
                'lightSpeedOutLeft' => 'lightSpeedOutLeft',
            ],
            'Rotating entrances' => [
                'rotateIn' => 'rotateIn',
                'rotateInDownLeft' => 'rotateInDownLeft',
                'rotateInDownRight' => 'rotateInDownRight',
                'rotateInUpLeft' => 'rotateInUpLeft',
                'rotateInUpRight' => 'rotateInUpRight',
            ],
            'Rotating exits' => [
                'rotateOut' => 'rotateOut',
                'rotateOutDownLeft' => 'rotateOutDownLeft',
                'rotateOutDownRight' => 'rotateOutDownRight',
                'rotateOutUpLeft' => 'rotateOutUpLeft',
                'rotateOutUpRight' => 'rotateOutUpRight',
            ],
            'Specials' => [
                'hinge' => 'hinge',
                'jackInTheBox' => 'jackInTheBox',
                'rollIn' => 'rollIn',
                'rollOut' => 'rollOut',
            ],
            'Zooming entrances' => [
                'zoomIn' => 'zoomIn',
                'zoomInDown' => 'zoomInDown',
                'zoomInLeft' => 'zoomInLeft',
                'zoomInRight' => 'zoomInRight',
                'zoomInUp' => 'zoomInUp',
            ],
            'Zooming exits' => [
                'zoomOut' => 'zoomOut',
                'zoomOutDown' => 'zoomOutDown',
                'zoomOutLeft' => 'zoomOutLeft',
                'zoomOutRight' => 'zoomOutRight',
                'zoomOutUp' => 'zoomOutUp',
            ],
            'Sliding entrances' => [
                'slideInDown' => 'slideInDown',
                'slideInLeft' => 'slideInLeft',
                'slideInRight' => 'slideInRight',
                'slideInUp' => 'slideInUp',
            ],
            'Sliding exits' => [
                'slideOutDown' => 'slideOutDown',
                'slideOutLeft' => 'slideOutLeft',
                'slideOutRight' => 'slideOutRight',
                'slideOutUp' => 'slideOutUp',
            ],
        ];

        $choices = [];
        foreach ($animationsGroups as $type => $animations) {
            foreach ($animations as $label => $value) {
                $choices[$type][$label.'-onload'] = $value.'-onload';
                $choices[$type][$label.'-onscroll'] = $value.'-onscroll';
                $choices[$type][$label.'-hover'] = $value.'-hover';
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
