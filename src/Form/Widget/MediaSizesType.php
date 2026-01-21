<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MediaSizesType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaSizesType
{
    private TranslatorInterface $translator;

    /**
     * MediaSizesType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * To add media sizes fields.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $fieldSize = $options['fieldSize'] ?? 6;

        $builder->add('maxWidth', IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Largeur (px) - Ordinateur', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une largeur', [], 'admin'),
                'tabSize' => !empty($options['tabSize']) ? $options['tabSize'] : '12',
                'group' => 'col-md-'.$fieldSize,
            ],
        ]);

        $builder->add('maxHeight', IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Hauteur (px) - Ordinateur', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une hauteur', [], 'admin'),
                'group' => 'col-md-'.$fieldSize,
            ],
        ]);

        $builder->add('tabletMaxWidth', IntegerType::class, [
            'required' => false,
            'label' => 'Largeur (px) - Tablette',
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une largeur', [], 'admin'),
                'group' => 'col-md-'.$fieldSize,
            ],
        ]);

        $builder->add('tabletMaxHeight', IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Hauteur (px) - Tablette', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une hauteur', [], 'admin'),
                'group' => 'col-md-'.$fieldSize,
            ],
        ]);

        $builder->add('mobileMaxWidth', IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Largeur (px) - Mobile', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une largeur', [], 'admin'),
                'group' => 'col-md-'.$fieldSize,
            ],
        ]);

        $builder->add('mobileMaxHeight', IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Hauteur (px) - Mobile', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une hauteur', [], 'admin'),
                'group' => 'col-md-'.$fieldSize,
            ],
        ]);
    }
}
