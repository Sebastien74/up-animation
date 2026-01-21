<?php

declare(strict_types=1);

namespace App\Form\Type\Translation;

use App\Entity\Translation\TranslationUnit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * UnitType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('translations', CollectionType::class, [
            'label' => false,
            'entry_type' => TranslationType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TranslationUnit::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
