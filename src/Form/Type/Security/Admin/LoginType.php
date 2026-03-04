<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LoginType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LoginType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * LoginType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $loginType = 'email' == $options['login_type'] ? Type\EmailType::class : Type\TextType::class;
        $loginInputName = 'email' == $options['login_type'] ? 'email' : 'login';
        $loginLabel = 'email' == $options['login_type']
            ? $this->translator->trans('Adresse e-mail', [], 'security_cms')
            : $this->translator->trans("Identifiant", [], 'security_cms');
        $loginPlaceholder = 'email' == $options['login_type']
            ? $this->translator->trans('Saisissez votre adresse e-mail', [], 'security_cms')
            : $this->translator->trans("Saisissez votre identifiant", [], 'security_cms');
        $constraints = [new NotBlank()];
        if (Type\EmailType::class === $loginType) {
            $constraints[] = new Email();
        }

        $builder->add($loginInputName, $loginType, [
            'label' => $loginLabel,
            'attr' => [
                'placeholder' => $loginPlaceholder,
                'autocomplete' => 'off',
                'autofocus' => false,
            ],
            'row_attr' => ['class' => 'col-12 form-floating'],
            'constraints' => $constraints,
        ]);

        $builder->add('_password', Type\PasswordType::class, [
            'label' => $this->translator->trans('Mot de passe', [], 'security_cms'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre mot de passe', [], 'security_cms'),
                'autocomplete' => 'off',
                'autofocus' => false,
            ],
            'row_attr' => ['class' => 'col-12 form-floating'],
            'constraints' => [new NotBlank()],
        ]);

        $builder->add('_remember_me', Type\CheckboxType::class, [
            'label' => $this->translator->trans('Se souvenir de moi', [], 'security_cms'),
            'required' => false,
            'data' => true,
        ]);

        $builder->add('field_ho', Type\TextType::class, [
            'mapped' => false,
            'label' => $this->translator->trans('Valeur'),
            'required' => true,
            'label_attr' => ['class' => 'col-12 d-none'],
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une valeur', [], 'security_cms'),
                'class' => 'col-12 form-field-none field_ho',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('field_ho_entitled', Type\TextType::class, [
            'mapped' => false,
            'label' => $this->translator->trans('Intitulé'),
            'label_attr' => ['class' => 'col-12 d-none'],
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'security_cms'),
                'class' => 'col-12 form-field-none',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Se connecter', [], 'security_cms'),
            'attr' => [
                'class' => 'col-12 btn-app w-100 center',
                'data-icon' => 'sign-in',
                'data-icon-side' => 'left',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            'data_class' => null,
            'login_type' => $_ENV['SECURITY_ADMIN_LOGIN_TYPE'],
            'website' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
