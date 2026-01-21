<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

use App\Form\Model\Security\Front\PasswordResetModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PasswordResetType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PasswordResetType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PasswordResetType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', Type\RepeatedType::class, [
            'label' => false,
            'type' => Type\PasswordType::class,
            'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'validators_cms'),
            'first_options' => [
                'label' => $this->translator->trans('Mot de passe', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                    'class' => 'password-checker',
                ],
            ],
            'second_options' => [
                'label' => $this->translator->trans('Confirmation du mot de passe', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                ],
                'help' => $this->translator->trans('Votre mot de passe doit comporter au moins 8 caractères, contenir au moins un chiffre, une majuscule et une minuscule.', [], 'security_cms'),
            ],
            'constraints' => [
                new Assert\Regex([
                    'message' => $this->translator->trans('Le mot de passe doit comporter au moins 8 caractères, contenir au moins un chiffre, une majuscule et une minuscule.', [], 'security_cms'),
                    'pattern' => '/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{8,}/',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordResetModel::class,
            'website' => null,
        ]);
    }
}
