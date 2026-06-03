<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

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
            : $this->translator->trans('Identifiant', [], 'security_cms');
        $loginPlaceholder = 'email' == $options['login_type']
            ? $this->translator->trans('Saisissez votre adresse e-mail', [], 'security_cms')
            : $this->translator->trans('Saisissez votre identifiant', [], 'security_cms');
        $constraints = [new NotBlank()];
        if (Type\EmailType::class === $loginType) {
            $constraints[] = new Email();
        }

        $builder->add($loginInputName, $loginType, [
            'label' => $loginLabel,
            'attr' => [
                'placeholder' => $loginPlaceholder,
                'autocomplete' => 'off',
                'autofocus' => false
            ],
            'row_attr' => ['class' => 'col-12 col-lg-12'],
            'constraints' => $constraints,
        ]);

        $builder->add('_password', Type\PasswordType::class, [
            'label' => $this->translator->trans('Mot de passe', [], 'security_cms'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez votre mot de passe', [], 'security_cms'),
                'autocomplete' => 'off',
                'autofocus' => false
            ],
            'row_attr' => ['class' => 'col-12 col-lg-12'],
            'constraints' => [new NotBlank()],
        ]);

        $builder->add('_remember_me', Type\CheckboxType::class, [
            'label' => $this->translator->trans('Se souvenir de moi', [], 'security_cms'),
            'required' => false,
            'data' => true,
        ]);

        $builder->add('field_ho', Type\TextType::class, [
            'mapped' => false,
            'required' => false,
            'label' => false,
            'attr' => [
                'class' => 'col-12 form-field-none field_ho',
                'autocomplete' => 'off',
                'tabindex' => '-1',
                'aria-hidden' => 'true',
            ],
            'row_attr' => ['class' => 'col-12 form-field-none', 'aria-hidden' => 'true'],
        ]);

        $builder->add('field_ho_entitled', Type\TextType::class, [
            'mapped' => false,
            'required' => false,
            'label' => false,
            'attr' => [
                'class' => 'col-12 form-field-none',
                'autocomplete' => 'off',
                'tabindex' => '-1',
                'aria-hidden' => 'true',
            ],
            'row_attr' => ['class' => 'col-12 form-field-none', 'aria-hidden' => 'true'],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Se connecter', [], 'security_cms'),
            'attr' => ['class' => 'col-12 btn btn-primary btn-block text-uppercase w-100 mt-3 d-flex justify-content-center'],
            'row_attr' => ['class' => 'col-12 col-lg-12'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            'data_class' => null,
            'login_type' => $_ENV['SECURITY_FRONT_LOGIN_TYPE'],
            'website' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
