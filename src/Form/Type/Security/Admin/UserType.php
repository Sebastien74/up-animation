<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Entity\Core\Website;
use App\Entity\Security\Company;
use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Form\Widget as WidgetType;
use App\Repository\Security\CompanyRepository;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
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
    private bool $isInternalUser;
    private bool $isUserManager;

    /**
     * UserType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CompanyRepository $companyRepository,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
        $this->isUserManager = in_array('ROLE_USERS', $user->getRoles()) || $this->isInternalUser;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();
        $haveCompanies = count($this->companyRepository->findAll()) > 0;

        $builder->add('login', Type\TextType::class, [
            'label' => $this->translator->trans("Nom d'utilisateur", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans("Saisissez un nom d'utilisateur", [], 'admin'),
                'group' => $isNew ? 'col-md-3' : 'col-md-4',
            ],
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('E-mail', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                'group' => $isNew ? 'col-md-3' : 'col-md-4',
            ],
            'constraints' => [new Assert\Email()],
        ]);

        if (!$isNew) {
            $builder->add('lastName', Type\TextType::class, [
                'label' => $this->translator->trans('Nom de famille', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                    'group' => 'col-md-4',
                ],
            ]);

            $builder->add('firstName', Type\TextType::class, [
                'label' => $this->translator->trans('Prénom', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un prénom', [], 'admin'),
                    'group' => 'col-md-4',
                ],
            ]);
        }

        if ($this->isUserManager) {
            $builder->add('group', EntityType::class, [
                'label' => $this->translator->trans('Groupe', [], 'admin'),
                'class' => Group::class,
                'display' => 'search',
                'query_builder' => function (EntityRepository $er) {
                    if (!$this->isInternalUser) {
                        return $er->createQueryBuilder('g')
                            ->andWhere('g.slug != :slug')
                            ->setParameter('slug', 'internal')
                            ->orderBy('g.adminName', 'ASC');
                    }

                    return $er->createQueryBuilder('g')
                        ->orderBy('g.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => $isNew ? 'col-md-3' : 'col-md-4'],
                'constraints' => [new Assert\NotBlank()],
            ]);
        }

        $builder->add('locale', WidgetType\LanguageIconType::class, [
            'label' => $this->translator->trans('Langue', [], 'admin'),
            'attr' => ['group' => $isNew ? 'col-md-3' : 'col-md-4'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $class = $isNew ? 'col-12' : 'col-12';
        $class = $isNew && $haveCompanies ? 'col-md-6' : $class;
        $builder->add('websites', EntityType::class, [
            'label' => 'Site(s)',
            'required' => true,
            'class' => Website::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->orderBy('c.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'multiple' => true,
            'display' => 'search',
            'attr' => [
                'data-placeholder' => $this->translator->trans('Séléctionnez', [], 'security_cms'),
                'group' => $class,
            ],
            'constraints' => [new Assert\Count(min: 1, minMessage: $this->translator->trans('Vous devez sélctionner au moins un site.', [], 'security_cms'))],
        ]);

        if ($haveCompanies) {
            $builder->add('companies', EntityType::class, [
                'label' => 'Entreprise(s)',
                'required' => false,
                'class' => Company::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getName());
                },
                'multiple' => true,
                'display' => 'search',
                'attr' => [
                    'placeholder' => $this->translator->trans('Séléctionnez', [], 'security_cms'),
                    'group' => $isNew ? 'col-md-6' : 'col-12',
                ],
            ]);
        }

        if ($isNew) {
            $builder->add('plainPassword', Type\RepeatedType::class, [
                'label' => false,
                'type' => Type\PasswordType::class,
                'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'validators_cms'),
                'first_options' => [
                    'label' => $this->translator->trans('Mot de passe', [], 'security_cms'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                        'group' => 'col-md-6 password-generator',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => $this->translator->trans('Veuillez saisir un mot de passe.', [], 'security_cms')]),
                    ],
                ],
                'second_options' => [
                    'label' => $this->translator->trans('Confirmation du mot de passe', [], 'security_cms'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                        'group' => 'col-md-6',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => $this->translator->trans('Veuillez confirmer votre mot de passe.', [], 'security_cms')]),
                    ],
                ],
            ]);
        } else {
            $builder->add('active', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer le compte', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('file', Type\FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*', 'class' => 'dropify'],
            ]);
        }

        if (!$isNew && $this->isInternalUser) {
            $builder->add('theme', WidgetType\AdminThemeType::class, [
                'label' => $this->translator->trans('Thème', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
