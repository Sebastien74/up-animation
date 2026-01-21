<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TextareaEditorExtension.
 *
 * Extends TextareaType
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TextareaEditorExtension implements FormTypeExtensionInterface
{
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $inAdmin = $this->coreLocator->inAdmin();
        if (true === $options['editor']) {
            $options['editor'] = 'tinymce';
        } elseif (!$inAdmin) {
            $options['editor'] = 'basic';
        }

        $class = $options['attr']['class'] ?? '';
        $view->vars['attr']['class'] = $class ? $class.' '.$options['editor'] : $options['editor'];
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
            'editor' => 'tinymce',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [TextareaType::class];
    }
}
