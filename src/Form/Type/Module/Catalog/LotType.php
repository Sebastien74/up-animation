<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Module\Catalog\Lot;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LotType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LotType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * LotType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reference', Type\TextType::class, [
            'label' => $this->translator->trans('Lot N°', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une référence', [], 'admin'),
                'group' => 'col-md-2',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('type', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Type', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une type', [], 'admin'),
                'group' => 'col-md-2',
            ],
        ]);

        $builder->add('surface', Type\NumberType::class, [
            'required' => false,
            'label' => $this->translator->trans('Surface', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une surface', [], 'admin'),
                'group' => 'col-md-2',
            ],
        ]);

        $builder->add('balconySurface', Type\NumberType::class, [
            'required' => false,
            'label' => $this->translator->trans('Surface (Balcon/Terrasse)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une surface', [], 'admin'),
                'group' => 'col-md-2',
            ],
        ]);

        $builder->add('price', Type\NumberType::class, [
            'required' => false,
            'label' => $this->translator->trans('Prix', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un prix', [], 'admin'),
                'group' => 'col-md-2',
            ],
        ]);

        $builder->add('sold', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Vendu', [], 'admin'),
            'attr' => ['group' => 'col-md-2 d-flex align-items-end', 'class' => 'w-100'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lot::class,
            'website' => null,
            'prototypePosition' => true,
            'translation_domain' => 'admin',
        ]);
    }
}
