<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ShapeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ShapeType extends AbstractType
{
    private const SHAPES = [
        'masked-wrap-first',
        'masked-wrap-second',
        'masked-wrap-third',
        'masked-wrap-fourth',
        'masked-wrap-fifth',
        'masked-wrap-sixth',
        'masked-wrap-seventh',
        'masked-wrap-eighth',
        'masked-wrap-ninth',
        'masked-wrap-tenth',
    ];

    private TranslatorInterface $translator;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Forme', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'required' => false,
            'expanded' => false,
            'multiple' => false,
            'display' => 'search',
            'choices' => $this->getShapes(),
            'choice_attr' => static fn (string $value): array => [
                'data-image' => '/medias/images/shapes/'.$value.'.svg',
                'data-text' => true,
            ],
            'attr' => ['class' => 'col-12 select-icons'],
            'translation_domain' => 'admin',
        ]);
    }

    private function getShapes(): array
    {
        $choices = [];
        foreach (self::SHAPES as $index => $value) {
            $choices[$this->translator->trans('Forme', [], 'admin').' '.($index + 1)] = $value;
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
