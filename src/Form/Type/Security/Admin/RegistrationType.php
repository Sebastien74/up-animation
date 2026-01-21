<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Form\Model\Security\Admin\RegistrationFormModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RegistrationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RegistrationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * RegistrationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('login', Type\TextType::class, [
            'label' => false,
            'required' => true,
            'attr' => [
                'placeholder' => $this->translator->trans('Identifiant *', [], 'security_cms'),
                'class' => 'pt-2 pb-2 material',
            ],
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('E-mail *', [], 'security_cms'),
                'class' => 'pt-2 pb-2 material',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('lastName', Type\EmailType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Nom *', [], 'security_cms'),
                'class' => 'pt-2 pb-2 material',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('firstName', Type\EmailType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Prénom *', [], 'security_cms'),
                'class' => 'pt-2 pb-2 material',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('plainPassword', Type\RepeatedType::class, [
            'label' => false,
            'type' => Type\PasswordType::class,
            'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'validators_cms'),
            'first_options' => [
                'label' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un mot de passe *', [], 'security_cms'),
                    'class' => 'pt-2 pb-2 material password-checker',
                ],
                'constraints' => [new Assert\NotBlank()],
            ],
            'second_options' => [
                'label' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Confirmez le mot de passe *', [], 'security_cms'),
                    'class' => 'pt-2 pb-2 material',
                ],
                'constraints' => [new Assert\NotBlank()],
            ],
        ]);

        $builder->add('agreeTerms', Type\CheckboxType::class, [
            'label' => $this->translator->trans('Conditions générales', [], 'security_cms'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegistrationFormModel::class,
            'website' => null,
        ]);
    }
}
