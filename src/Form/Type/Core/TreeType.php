<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TreeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TreeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nestable_output', null, [
            'label' => false,
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'old_position' => null,
            'iterations' => 0,
            'translation_domain' => 'admin',
        ]);
    }
}
