<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Module\Catalog\ProductInformation;
use Symfony\Component\Form\AbstractType;
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
        $builder->add('address', AddressType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductInformation::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
