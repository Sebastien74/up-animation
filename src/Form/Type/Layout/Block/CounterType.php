<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CounterType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CounterType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CounterType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'disableTitle' => true,
            'fields' => ['title' => 'col-md-4', 'placeholder' => 'col-md-4', 'slug' => 'col-md-4', 'body'],
            'label_fields' => [
                'title' => $this->translator->trans('Valeur', [], 'admin'),
                'placeholder' => $this->translator->trans('Préfixe', [], 'admin'),
                'slug' => $this->translator->trans('Suffixe', [], 'admin'),
            ],
            'placeholder_fields' => [
                'title' => $this->translator->trans('Saisissez une valeur', [], 'admin'),
                'placeholder' => $this->translator->trans('Saisissez un préfixe', [], 'admin'),
                'slug' => $this->translator->trans('Saisissez un suffixe', [], 'admin'),
            ],
            'fields_type' => [
                'title' => IntegerType::class,
            ],
        ]);

        $builder->add('icon', WidgetType\IconType::class, [
            'required' => true,
            'attr' => ['class' => 'select-icons', 'group' => 'col-md-4'],
        ]);

        $builder->add('color', WidgetType\AppColorType::class, [
            'label' => $this->translator->trans("Couleur de l'icône", [], 'admin'),
            'attr' => ['class' => 'select-icons', 'group' => 'col-md-4'],
        ]);

        $builder->add('iconSize', ChoiceType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->translator->trans("Taille de l'icône", [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => 'col-md-4'],
            'choices' => ['XS' => 'xs', 'S' => 'sm', 'M' => 'md', 'L' => 'lg', 'XL' => 'xl', 'XXL' => 'xxl'],
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
