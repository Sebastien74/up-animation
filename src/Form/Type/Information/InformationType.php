<?php

declare(strict_types=1);

namespace App\Form\Type\Information;

use App\Entity\Core\Website;
use App\Entity\Information\Information;
use App\Entity\Security\User;
use App\Form\Validator\SmartList;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * InformationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?User $user;

    /**
     * InformationType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Website $website */
        $website = $options['website'];
        $multiLocales = count($website->getConfiguration()->getAllLocales()) > 1;

        $intlsFields = ['title', 'introduction' => 'col-12 editor', 'body'];
        if (in_array('ROLE_ALERT', $this->user->getRoles())) {
            $intlsFields['placeholder'] = 'col-12 editor';
            $intlsFields['active'] = 'col-md-4';
//            $intlsFields['newTab'] = 'col-md-6';
        }

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $website,
            'fields' => $intlsFields,
            'extra_fields' => [
                'alertType' => [
                    'type' => Type\ChoiceType::class,
                    'required' => false,
                    'display' => 'search',
                    'label' => $this->translator->trans("Type d'alerte", [], 'admin'),
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'choices' => [
                        $this->translator->trans("Défilement", [], 'admin') => 'marquee',
                        $this->translator->trans("Rotation", [], 'admin') => 'flip',
                        $this->translator->trans("Classique", [], 'admin') => 'basic',
                    ],
                    'attr' => [
                        'group' => 'col-md-3',
                    ],
                ],
                'alertDuration' => [
                    'type' => Type\IntegerType::class,
                    'required' => false,
                    'label' => $this->translator->trans("Durée de défilement de l'alerte", [], 'admin'),
                    'attr' => [
                        'group' => 'col-md-3',
                        'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin'),
                    ],
                    'help' => $this->translator->trans("En secondes", [], 'admin'),
                ],
            ],
            'excludes_fields' => ['externalLink'],
            'fields_type' => ['placeholder' => Type\TextareaType::class],
            'constraints_fields' => ['placeholder' => new SmartList()],
            'help_fields' => ['placeholder' => $this->translator->trans('Pour vos messages faites une liste à puces', [], 'admin')],
            'label_fields' => [
                'title' => $this->translator->trans('Raison sociale', [], 'admin'),
                'introduction' => $this->translator->trans('Description pied de page', [], 'admin'),
                'body' => $this->translator->trans('Horaires', [], 'admin'),
                'placeholder' => $this->translator->trans("Message d'alerte", [], 'admin'),
                'active' => $this->translator->trans("Activer le message d'alerte", [], 'admin'),
                'newTab' => $this->translator->trans("Supprimer l'alerte au click", [], 'admin'),
            ],
            'placeholder_fields' => [
                'title' => $this->translator->trans('Saisissez une raison sociale', [], 'admin'),
                'introduction' => $this->translator->trans('Saisissez une description', [], 'admin'),
                'body' => $this->translator->trans('Saisissez vos horaires', [], 'admin'),
                'placeholder' => $this->translator->trans('Saisissez une alerte', [], 'admin'),
            ],
        ]);

        $builder->add('phones', Type\CollectionType::class, [
            'label' => false,
            'entry_type' => PhoneType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => [
                    'class' => 'phone',
                    'icon' => 'fal phone',
                    'group' => 'col-md-3',
                    'caption' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
                    'button' => $this->translator->trans('Ajouter un numéro', [], 'admin'),
                ],
                'website' => $website,
            ],
        ]);

        $builder->add('emails', Type\CollectionType::class, [
            'label' => false,
            'entry_type' => EmailType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => [
                    'class' => 'email',
                    'icon' => 'fal at',
                    'group' => 'col-md-3',
                    'caption' => $this->translator->trans('E-mails', [], 'admin'),
                    'button' => $this->translator->trans('Ajouter un e-mail', [], 'admin'),
                ],
                'website' => $website,
            ],
        ]);

        $builder->add('addresses', Type\CollectionType::class, [
            'label' => false,
            'entry_type' => AddressType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => [
                    'class' => 'address',
                    'icon' => 'fal map-marked-alt',
                    'group' => 'col-md-12',
                    'caption' => $this->translator->trans('Adresses', [], 'admin'),
                    'button' => $this->translator->trans('Ajouter une adresse', [], 'admin'),
                ],
                'website' => $website,
            ],
        ]);

        $builder->add('legals', Type\CollectionType::class, [
            'label' => false,
            'entry_type' => LegalType::class,
            'allow_add' => true,
            'prototype' => true,
            'by_reference' => false,
            'entry_options' => [
                'attr' => [
                    'class' => 'legals',
                    'icon' => 'fal balance-scale-left',
                    'group' => 'col-12',
                    'deletable' => $multiLocales,
                    'caption' => $this->translator->trans('Mentions légales', [], 'admin'),
                    'button' => $multiLocales ? $this->translator->trans('Ajouter des informations', [], 'admin') : false,
                ],
                'website' => $website,
            ],
        ]);

        $builder->add('socialNetworks', Type\CollectionType::class, [
            'label' => false,
            'entry_type' => SocialNetworkType::class,
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Information::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
