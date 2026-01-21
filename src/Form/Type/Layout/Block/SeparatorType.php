<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SeparatorType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeparatorType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SeparatorType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        $builder->add('height', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Hauteur du séparateur (px)', [], 'admin'),
            'attr' => [
                'group' => 'col-md-4',
                'placeholder' => $this->translator->trans('Saisissez une hauteur', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('width', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Largeur du séparateur (px)', [], 'admin'),
            'attr' => [
                'group' => 'col-md-4',
                'placeholder' => $this->translator->trans('Saisissez une hauteur', [], 'admin'),
            ],
        ]);

        $builder->add('color', WidgetType\BackgroundColorSelectType::class, [
            'label' => $this->translator->trans('Couleur de fond', [], 'admin'),
            'expanded' => false,
            'attr' => [
                'class' => 'select-icons',
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('hideMobile', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Cacher le séparateur en mobile', [], 'admin'),
            'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $radiusType = new WidgetType\RadiusType($this->coreLocator);
        $radiusType->add($builder);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_back' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
