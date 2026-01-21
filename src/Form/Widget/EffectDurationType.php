<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EffectDurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EffectDurationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * EffectDurationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin'),
                'group' => 'col-md-6 mb-md-0',
            ],
            'label' => $this->translator->trans('Durée', [], 'admin'),
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
