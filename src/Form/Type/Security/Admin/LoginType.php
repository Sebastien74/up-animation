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
        $loginPlaceholder = 'email' == $options['login_type']
            ? $this->translator->trans('E-mail', [], 'security_cms')
            : $this->translator->trans("Nom d'utilisateur", [], 'security_cms');
        $constraints = [new NotBlank()];
        if (Type\EmailType::class === $loginType) {
            $constraints[] = new Email();
        }

        $builder->add($loginInputName, $loginType, [
            'label' => false,
            'attr' => [
                'placeholder' => $loginPlaceholder,
                'autocomplete' => 'off',
                'autofocus' => false,
                'class' => 'pt-2 pb-2 material',
                'group' => 'col-12 mb-3',
            ],
            'constraints' => $constraints,
        ]);

        $builder->add('_password', Type\PasswordType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Mot de passe', [], 'security_cms'),
                'autocomplete' => 'off',
                'autofocus' => false,
                'class' => 'pt-2 pb-2 material',
                'group' => 'col-12 mb-3',
            ],
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
            'label_attr' => ['class' => 'd-none'],
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une valeur', [], 'security_cms'),
                'class' => 'form-field-none field_ho',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('field_ho_entitled', Type\TextType::class, [
            'mapped' => false,
            'label' => $this->translator->trans('Intitulé'),
            'label_attr' => ['class' => 'd-none'],
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'security_cms'),
                'class' => 'form-field-none',
                'autocomplete' => 'off',
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Se connecter', [], 'security_cms'),
            'attr' => [
                'group' => 'col-lg-12',
                'class' => 'btn btn-lg btn-info btn-block text-uppercase w-100',
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
