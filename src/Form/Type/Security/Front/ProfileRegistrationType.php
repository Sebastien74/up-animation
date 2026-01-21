<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

use App\Entity\Security\Profile;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ProfileRegistrationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProfileRegistrationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ProfileRegistrationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fields = $options['fields'];

        if (in_array('gender', $fields)) {
            $builder->add('gender', Type\ChoiceType::class, [
                'label' => false,
                'expanded' => true,
                'display' => 'inline',
                'attr' => ['group' => 'mb-0'],
                'choices' => [
                    $this->translator->trans('M.', [], 'security_cms') => 'mr',
                    $this->translator->trans('Mme', [], 'security_cms') => 'ms',
                ],
            ]);
        }

        if (in_array('phones', $fields)) {
            $builder->add('phones', CollectionType::class, [
                'label' => false,
                'entry_type' => PhoneFrontType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => [
                    'website' => $options['website'],
                ],
            ]);
        }

        if (in_array('addresses', $fields)) {
            $builder->add('addresses', CollectionType::class, [
                'label' => false,
                'entry_type' => AddressFrontType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => [
                    'website' => $options['website'],
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
            'fields' => [],
            'website' => null,
        ]);
    }
}
