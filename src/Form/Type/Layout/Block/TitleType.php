<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TitleType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TitleType extends AbstractType
{
    /**
     * TitleType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => [
                'title' => 'col-md-5',
                'subTitle' => 'col-md-5',
                'subTitlePosition' => 'col-md-4',
                'targetPage' => 'col-md-4',
                'targetLink' => 'col-md-4',
            ],
            'target_config' => false,
            'title_force' => true,
            'fields_data' => ['titleForce' => 2],
        ]);

        $builder->add('color', WidgetType\AppColorType::class, [
            'attr' => ['class' => 'select-icons', 'group' => 'col-md-3'],
        ]);

        $configs = new WidgetType\ContentConfigType($this->coreLocator);
        $configs->add($builder, [
            'website' => $options['website'],
            'fields' => ['fontWeight' => 'col-md-3', 'fontSize' => 'col-md-3', 'fontFamily' => 'col-md-3', 'italic' => 'col-md-3', 'uppercase' => 'col-md-3'],
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
