<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\Grid;
use App\Form\Widget as WidgetType;
use App\Form\Widget\AdminNameType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * GridType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GridType extends AbstractType
{
    /**
     * GridType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new AdminNameType($this->coreLocator);
        $adminName->add($builder);

        if (!$isNew) {
            $builder->add('cols', CollectionType::class, [
                'label' => false,
                'entry_type' => GridColType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => [
                    'attr' => ['class' => 'grid-col'],
                ],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Grid::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
