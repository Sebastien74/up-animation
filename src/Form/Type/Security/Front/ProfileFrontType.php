<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

use App\Entity\Security\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ProfileFrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProfileFrontType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
//        //        $builder->add('gender', Type\ChoiceType::class, [
//        //            'label' => false,
//        //            'expanded' => true,
//        //            'choices' => [
//        //                $this->translator->trans('M.', [], 'security_cms') => 'mr',
//        //                $this->translator->trans('Mme', [], 'security_cms') => 'ms',
//        //            ],
//        //            'attr' => ['group' => 'col-12'],
//        //            'constraints' => [
//        //                new Assert\NotBlank([
//        //                    'message' => $this->translator->trans("Veuillez séléctionnez un genre.", [], 'admin')
//        //                ])
//        //            ]
//        //        ]);
//
//        if ($_ENV['SECURITY_FRONT_ADDRESSES']) {
//            $builder->add('addresses', CollectionType::class, [
//                'label' => false,
//                'entry_type' => AddressFrontType::class,
//                'entry_options' => ['website' => $options['website']],
//            ]);
//        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
            'website' => null,
        ]);
    }
}
