<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AlignmentType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AlignmentType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AlignmentType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Alignement des contenus', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Par défaut', [], 'admin'),
            'choices' => [
                $this->translator->trans('Gauche', [], 'admin') => 'start',
                $this->translator->trans('Centré', [], 'admin') => 'center',
                $this->translator->trans('Droite', [], 'admin') => 'end',
                $this->translator->trans('Justifié', [], 'admin') => 'justify',
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
