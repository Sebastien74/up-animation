<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ChoiceExtension.
 *
 * Extends ChoiceType
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ChoiceExtension implements FormTypeExtensionInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $noSelect2Fields = ['day', 'month', 'year', 'hour', 'minute'];
        $fieldName = $view->vars['name'];
        $setDisplay = 'search' === $options['display'] ? 'select-2' : $options['display'];
        $display = !in_array($fieldName, $noSelect2Fields) ? $setDisplay : 'classic';

        if ('select-flags' === $display) {
            $view->vars['attr']['group'] = !empty($options['attr']['group']) ? $options['attr']['group'].' select-flags' : 'col-12 select-flags';
            $display = 'select-icons select-flags';
        }

        if ('select-2' == $setDisplay) {
            $view->vars['attr']['group'] = !empty($options['attr']['group']) ? $options['attr']['group'].' select2-group' : 'col-12 select2-group';
        }

        $view->vars['attr']['class'] = !empty($options['attr']['class'])
            ? $options['attr']['class'].' '.$display
            : $display;

        if ('inline' === $options['display']) {
            $view->vars['attr']['class'] = $view->vars['attr']['class'].' form-check-inline d-flex flex-wrap';
        }

        if ('search' === $options['display'] && !str_contains($view->vars['attr']['class'], 'select-choice')) {
            $view->vars['attr']['class'] = $view->vars['attr']['class'].' select-choice';
        }

        //        if (!empty($view->vars['attr']['group'])) {
        //            $view->vars['attr']['class'] = $view->vars['attr']['class'] . ' ' . $view->vars['attr']['group'];
        //        }

        $view->vars['attr']['data-dropdown-class'] = 'select-dropdown-container' != $options['dropdown_class']
            ? $options['dropdown_class']
            : ($options['multiple'] ? $options['dropdown_class'].'-multiple' : $options['dropdown_class'].'-single');

        $view->vars['label_attr']['class'] = !empty($options['label_class']['class'])
            ? $options['attr']['class'].' '.$options['label_class']
            : $options['label_class'];

        $view->vars['customized_options'] = $options['customized_options'];
        $view->vars['disabled_check'] = $options['disabled_check'];
        $view->vars['display'] = $setDisplay;
        $view->vars['color'] = $options['color'];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'customized_options' => [],
            'disabled_check' => false,
            'display' => null,
            'color' => null,
            'dropdown_class' => 'select-dropdown-container',
            'label_class' => '',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChoiceType::class];
    }
}
