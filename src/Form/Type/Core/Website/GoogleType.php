<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Api\Google;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * GoogleType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GoogleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('youtubeNbrItems', IntegerType::class, [
            'label' => "Nombre de vidéos YouTube",
            'required' => false,
            'attr' => ['min' => 1, 'max' => 50],
        ]);

        $builder->add('googleReviewsNbrItems', IntegerType::class, [
            'label' => "Nombre d'avis Google",
            'required' => false,
            'attr' => ['min' => 1, 'max' => 5],
        ]);

        $builder->add('intls', CollectionType::class, [
            'label' => false,
            'entry_type' => GoogleIntlType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Google::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
