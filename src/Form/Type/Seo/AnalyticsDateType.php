<?php

declare(strict_types=1);

namespace App\Form\Type\Seo;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * AnalyticsDateType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AnalyticsDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('date', BirthdayType::class, [
            'widget' => 'single_text',
            'format' => $options['format'],
            'html5' => false,
            'attr' => ['class' => 'js-datepicker'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'format' => null,
            'csrf_protection' => false,
            'translation_domain' => 'admin',
        ]);
    }

    public function getBlockPrefix(): ?string
    {
        return '';
    }
}
