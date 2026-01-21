<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Entity\Media\ThumbConfiguration;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ThumbConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ThumbConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ThumbConfigurationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => 'col-md-12']);

        $builder->add('width', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Largeur (px)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une largeur', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('height', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Hauteur (px)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une hauteur', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('screen', Type\ChoiceType::class, [
            'required' => false,
            'label' => $this->translator->trans('Écrans', [], 'admin'),
            'choices' => [
                $this->translator->trans('Ordinateur', [], 'admin') => 'desktop',
                $this->translator->trans('Tablette', [], 'admin') => 'tablet',
                $this->translator->trans('Mobile', [], 'admin') => 'mobile',
            ],
            'display' => 'search',
            'attr' => ['group' => 'col-md-4'],
        ]);

        if (!$isNew) {

            $builder->add('fixedHeight', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Conserver la hauteur définie', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('actions', Type\CollectionType::class, [
                'required' => false,
                'label' => false,
                'entry_type' => ThumbActionType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => [
                    'attr' => [
                        'class' => 'configuration',
                    ],
                    'website' => $options['website'],
                ],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ThumbConfiguration::class,
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
