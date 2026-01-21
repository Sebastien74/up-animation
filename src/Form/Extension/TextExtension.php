<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TextExtension.
 *
 * Extends TextType
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TextExtension implements FormTypeExtensionInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!empty($options['counter'])) {
            $view->vars['counter'] = $options['counter'];
        }

        if (!empty($options['bytes'])) {
            $view->vars['bytes'] = $options['bytes'];
        }

        if (!empty($options['display'])) {
            $view->vars['display'] = $options['display'];
        }

        if (!empty($options['role'])) {
            $view->vars['attr']['data-role'] = $options['role'];
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
            'editor' => null,
            'counter' => null,
            'bytes' => null,
            'display' => null,
            'role' => null,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [TextType::class];
    }
}
