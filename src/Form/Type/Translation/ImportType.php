<?php

declare(strict_types=1);

namespace App\Form\Type\Translation;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ImportType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('files', FileType::class, [
            'label' => false,
            'multiple' => true,
            'attr' => ['accept' => '.xlsx'],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\File([
                    'mimeTypes' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
