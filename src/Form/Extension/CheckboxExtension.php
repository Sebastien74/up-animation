<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CheckboxExtension.
 *
 * Extends CheckboxType
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CheckboxExtension implements FormTypeExtensionInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $class = !empty($options['attr']['class']) ? $options['attr']['class'] : '';
        if ('custom' === $options['display']) {
            $labelAttr = !empty($options['label_attr']['class']) ? $options['label_attr']['class'].' ' : '';
            $view->vars['label_attr']['class'] = $labelAttr.'custom-control-label form-check-label cursor mb-0';
            $class .= $class.' form-check-input';
        } elseif ('switch' === $options['display']) {
            $view->vars['label_attr']['class'] = 'checkbox-inline checkbox-switch cursor mb-0';
        } elseif ('button' === $options['display']) {
            $view->vars['label_attr']['class'] = 'button';
            $view->vars['attr']['data-color'] = $options['color'];
        }

        if ($class) {
            $view->vars['attr']['class'] = $class;
        }

        $group = !empty($options['attr']['group']) ? $options['attr']['group'].' ' : 'col-12 ';
        $view->vars['attr']['group'] = $group.'checkbox-group';

        $parentConfiguration = $form->getParent()->getConfig();
        $choiceList = !empty($form->getParent()->getConfig()->getAttributes()['choice_list']) ? $form->getParent()->getConfig()->getAttributes()['choice_list'] : null;
        if ($parentConfiguration->getRequired() && $choiceList && 1 === count($choiceList->getChoices())) {
            $view->vars['required'] = true;
        }

        if ($options['uniq_id']) {
            $view->vars['id'] = $form->getName().'-'.uniqid();
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
            'display' => 'checkbox-custom',
            'color' => 'primary',
            'uniq_id' => true,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [CheckboxType::class];
    }
}
