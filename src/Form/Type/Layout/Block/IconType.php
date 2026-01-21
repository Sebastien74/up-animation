<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * IconType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IconType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * IconType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        $builder->add('icon', WidgetType\IconType::class, [
            'attr' => ['class' => 'select-icons', 'group' => 'col-md-3'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('color', WidgetType\AppColorType::class, [
            'label' => $this->translator->trans("Couleur de l'icône", [], 'admin'),
            'attr' => ['class' => 'select-icons', 'group' => 'col-md-3'],
        ]);

        $builder->add('backgroundColorType', WidgetType\BackgroundColorSelectType::class, [
            'label' => $this->translator->trans("Couleur de l'icône au hover", [], 'admin'),
            'attr' => ['class' => ' select-icons', 'group' => 'col-md-3'],
        ]);

        $builder->add('iconSize', ChoiceType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->translator->trans("Taille de l'icône", [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => 'col-md-3'],
            'choices' => ['XS' => 'xs', 'S' => 'sm', 'M' => 'md', 'L' => 'lg', 'XL' => 'xl', 'XXL' => 'xxl'],
        ]);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => [
                'title' => 'col-md-9',
                'targetPage' => 'col-md-3',
            ],
            'target_config' => false,
            'title_force' => false,
        ]);

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
