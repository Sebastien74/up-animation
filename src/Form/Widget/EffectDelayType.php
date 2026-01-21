<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EffectDelayType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EffectDelayType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * EffectDelayType constructor.
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
                'placeholder' => $this->translator->trans('Saisissez un délai', [], 'admin'),
                'group' => 'col-md-6 mb-md-0',
            ],
            'label' => $this->translator->trans('Délai', [], 'admin'),
        ]);
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
