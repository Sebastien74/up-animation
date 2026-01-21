<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MainExtension.
 *
 * Extends RadioType
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MainExtension implements FormTypeExtensionInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['row_attr']['class'] = !empty($view->vars['row_attr']['class']) ? $view->vars['row_attr']['class'].' group-form' : 'group-form';

        if (!empty($view->vars['attr']['group'])) {
            $view->vars['attr']['data-group'] = $view->vars['attr']['group'];
            unset($view->vars['attr']['group']);
        }

        if (isset($options['media_modal_copy']) && $options['media_modal_copy']) {
            $view->vars['attr']['data-media-modal-copy'] = $options['media_modal_copy'];
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
    }

    /**
     * configureOptions.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'media_modal_copy' => false,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            Type\TextType::class,
            Type\TextareaType::class,
            Type\ChoiceType::class,
            Type\CheckboxType::class,
            Type\RadioType::class,
            Type\SubmitType::class,
            Type\ButtonType::class,
            Type\FileType::class,
            Type\DateType::class,
            Type\DateTimeType::class,
            Type\NumberType::class,
            Type\IntegerType::class,
        ];
    }
}
