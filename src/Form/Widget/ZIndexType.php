<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ZIndexType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZIndexType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ZIndexType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];
        for ($i = 1; $i <= 10; $i++) {
            $index = $i * 100;
            $choices[$index] = $index;
        }

        $resolver->setDefaults([
            'required' => false,
            'label' => $this->translator->trans('Z-index', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'display' => 'search',
            'attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'group' => 'col-md-4',
                ]);
            },
            'choices' => $choices,
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}