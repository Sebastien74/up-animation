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
            'label' => $this->translator->trans('Identifiant', [], 'security_cms'),
            'required' => true,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre identifiant', [], 'security_cms'),
            ],
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('Adresse e-mail', [], 'security_cms'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre adresse e-mail', [], 'security_cms'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('lastName', Type\TextType::class, [
            'label' => $this->translator->trans('Nom', [], 'security_cms'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre nom', [], 'security_cms'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('firstName', Type\TextType::class, [
            'label' => $this->translator->trans('Prénom', [], 'security_cms'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre prénom', [], 'security_cms'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('plainPassword', Type\RepeatedType::class, [
            'label' => false,
            'type' => Type\PasswordType::class,
            'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'validators_cms'),
            'first_options' => [
                'label' => $this->translator->trans('Mot de passe', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un mot de passe', [], 'security_cms'),
                    'class' => 'col-12 password-checker',
                ],
                'constraints' => [new Assert\NotBlank()],
            ],
            'second_options' => [
                'label' => $this->translator->trans('Confirmation du mot de passe', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Confirmez le mot de passe', [], 'security_cms'),
                ],
                'constraints' => [new Assert\NotBlank()],
            ],
        ]);

        $builder->add('agreeTerms', Type\CheckboxType::class, [
            'label' => $this->translator->trans('Conditions générales', [], 'security_cms'),
            'attr' => [
                'class' => 'col-12 pt-2 pb-2 material',
            ],
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
