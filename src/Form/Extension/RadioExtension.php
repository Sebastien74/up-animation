<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * RadioExtension.
 *
 * Extends RadioType
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RadioExtension implements FormTypeExtensionInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['label_attr']['class'] = $options['style'];

        if (!empty($view->vars['form']) && is_object($view->vars['form']) && property_exists($view->vars['form'], 'parent')
            && is_object($view->vars['form']->parent) && property_exists($view->vars['form']->parent, 'vars')) {
            $parentClass = !empty($view->vars['form']->parent->vars['attr']['class']) ? $view->vars['form']->parent->vars['attr']['class'] : null;
            if ($parentClass && str_contains($parentClass, 'inline')) {
                $view->vars['label_attr']['class'] = 'inline';
            }
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
            'style' => 'radio-custom',
            'display' => null,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [RadioType::class];
    }
}
