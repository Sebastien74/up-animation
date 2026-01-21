<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Configuration;

use App\Entity\Information\Information;
use App\Form\Type\Information\SocialNetworkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * InformationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('socialNetworks', CollectionType::class, [
            'label' => false,
            'entry_type' => SocialNetworkType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Information::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
