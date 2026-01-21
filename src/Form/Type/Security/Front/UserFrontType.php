<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

use App\Entity\Security\UserFront;
use App\Entity\Security\UserRequest;
use App\Form\Validator\UniqUserEmail;
use App\Form\Validator\UniqUserLogin;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UserFrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserFrontType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * UserFrontType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('lastName', Type\TextType::class, [
            'label' => $this->translator->trans('Nom', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => 'col-md-6',
            ],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir votre nom.', [], 'admin')),
            ],
        ]);

        $builder->add('firstName', Type\TextType::class, [
            'label' => $this->translator->trans('Prénom', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un prénom', [], 'admin'),
                'group' => 'col-md-6',
            ],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir votre prénom.', [], 'admin')),
            ],
        ]);

        $builder->add('login', Type\TextType::class, [
            'label' => $this->translator->trans("Nom d'utilisateur", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => 'col-md-6',
            ],
            'constraints' => [
                new UniqUserLogin(),
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir un identifiant.', [], 'admin')),
            ],
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('E-mail', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                'group' => 'col-md-6',
            ],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir un email.', [], 'admin')),
                new Assert\Email(),
                new UniqUserEmail(),
            ],
        ]);

//        $builder->add('profile', ProfileFrontType::class);

        if ((bool) $_ENV['SECURITY_FRONT_PROFILE_IMG'] === true) {
            $builder->add('file', Type\FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*', 'class' => 'dropify'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserRequest::class,
            'website' => null,
            'translation_domain' => 'front',
        ]);
    }
}
