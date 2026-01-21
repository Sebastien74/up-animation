<?php

declare(strict_types=1);

namespace App\Form\Type\Seo\Configuration;

use App\Entity\Api\Facebook;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FacebookType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FacebookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('intls', CollectionType::class, [
            'label' => false,
            'entry_type' => FacebookIntlType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facebook::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
