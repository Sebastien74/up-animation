<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

use App\Entity\Security\Company;
use App\Entity\Security\UserFront;
use App\Form\Widget as WidgetType;
use App\Repository\Security\CompanyRepository;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UserType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * UserType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CompanyRepository    $companyRepository,
    )
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();
        $haveCompanies = count($this->companyRepository->findAll()) > 0;

        $builder->add('login', Type\TextType::class, [
            'label' => $this->translator->trans("Nom d'utilisateur", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans("Saisissez un nom d'utilisateur", [], 'admin'),
                'row_attr' => ['class' => $isNew && $haveCompanies ? 'col-md-3' : 'col-md-4'],
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('E-mail', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                'row_attr' => ['class' => $isNew && $haveCompanies ? 'col-md-3' : 'col-md-4'],
            ],
            'constraints' => [new Assert\Email()],
        ]);

        if (!$isNew) {
            $builder->add('lastName', Type\TextType::class, [
                'label' => $this->translator->trans('Nom de famille', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('firstName', Type\TextType::class, [
                'label' => $this->translator->trans('Prénom', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un prénom', [], 'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);
        }

        $builder->add('locale', WidgetType\LanguageIconType::class, [
            'label' => $this->translator->trans('Langue', [], 'admin'),

            'row_attr' => ['class' => $isNew && $haveCompanies ? 'col-md-3' : 'col-md-4'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        if ($haveCompanies) {
            $builder->add('company', EntityType::class, [
                'label' => $this->translator->trans('Entreprise', [], 'admin'),
                'required' => false,
                'class' => Company::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getName());
                },
                'display' => 'search',
                'attr' => [
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'row_attr' => ['class' => $isNew ? 'col-md-3' : 'col-md-4'],
                ],
            ]);
        }

        if ($isNew) {
            $builder->add('plainPassword', Type\RepeatedType::class, [
                'label' => false,
                'type' => Type\PasswordType::class,
                'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'admin'),
                'first_options' => [
                    'label' => $this->translator->trans('Mot de passe', [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'admin')
                    ],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6 password-generator'],
                    'constraints' => [new Assert\NotBlank()],
                ],
                'second_options' => [
                    'label' => $this->translator->trans('Confirmation du mot de passe', [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'admin')
                    ],
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
                    'constraints' => [new Assert\NotBlank()],
                ],
            ]);
        } else {
            $builder->add('active', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Activer le compte', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4 d-flex align-items-end'],
            ]);

            $builder->add('file', Type\FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*', 'class' => 'col-12 dropify'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserFront::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
