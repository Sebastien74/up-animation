<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * HideType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class HideType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * HideType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Cacher', [], 'admin'),
            'attr' => ['group' => 'col-12', 'class' => 'w-100'],
        ]);
    }

    public function getParent(): ?string
    {
        return CheckboxType::class;
    }
}
