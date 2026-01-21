<?php

declare(strict_types=1);

namespace App\Form\Type\Seo;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * WebsiteRedirectionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteRedirectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('redirections', CollectionType::class, [
            'label' => false,
            'mapped' => false,
            'entry_type' => RedirectionType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => ['class' => 'redirection'],
                'website' => $options['website'],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'not_found_url' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
