<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Form\Widget\MediaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MediaUploadType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('medias', MediaType::class, [
            'label' => false,
            'mapped' => false,
            'multiple' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'csrf_protection' => false,
            'translation_domain' => 'admin',
        ]);
    }
}
