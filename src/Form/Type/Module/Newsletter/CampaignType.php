<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newsletter;

use App\Entity\Module\Newsletter\Campaign;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CampaignType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CampaignType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * CampaignType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug-internal' => $this->isInternalUser]);

        if (!$isNew && !$this->isInternalUser) {
            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'data_config' => true,
                'fields' => ['introduction'],
                'label_fields' => [
                    'introduction' => $this->translator->trans('Info RGPD', [], 'admin'),
                ],
            ]);
        }

        if (!$isNew && $this->isInternalUser) {
            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'data_config' => true,
                'fields' => ['introduction', 'body', 'placeholder' => 'col-12'],
                'label_fields' => [
                    'body' => $this->translator->trans("Corps de l'e-mail de confirmation", [], 'admin'),
                    'introduction' => $this->translator->trans('Info RGPD', [], 'admin'),
                    'placeholder' => $this->translator->trans('Message popup de remerciement', [], 'admin'),
                ],
            ]);

            $builder->add('externalFormAction', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Action du formulaire', [], 'admin'),
                'attr' => [
                    'subtitle' => $this->translator->trans('ConfigurationModel Mailchimp', [], 'admin'),
                    'placeholder' => $this->translator->trans("Saisissez l'action", [], 'admin'),
                    'group' => 'col-md-6',
                ],
            ]);

            $builder->add('externalFieldEmail', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nom du champs de mail', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez le nom', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('externalFormToken', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nom du champs token', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez le token', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('internalRegistration', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans("Activer l'enregistrement interne", [], 'admin'),
                'attr' => ['group' => 'col-md-6', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('mailjetListName', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nom de la campagne', [], 'admin'),
                'attr' => [
                    'subtitle' => $this->translator->trans('ConfigurationModel Mailjet', [], 'admin'),
                    'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('mailjetListId', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('ID de la campagne', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un ID', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('mailjetPublicKey', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Clé publique', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('mailjetSecretKey', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Clé privée', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('recaptcha', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer le recaptcha', [], 'admin'),
                'attr' => ['group' => 'col-md-6', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('emailConfirmation', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Envoyer un e-mail de confirmation', [], 'admin'),
                'attr' => ['group' => 'col-md-6', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('emailToWebmaster', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Envoyer un e-mail aux administrateurs', [], 'admin'),
                'attr' => ['group' => 'col-md-6 d-flex align-items-end', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('receivingEmails', WidgetType\TagInputType::class, [
                'label' => $this->translator->trans('E-mails des administrateurs', [], 'admin'),
                'required' => false,
                'attr' => [
                    'data-config' => true,
                    'group' => 'col-md-8',
                    'placeholder' => $this->translator->trans('Ajouter des e-mails', [], 'admin'),
                ],
            ]);

            $builder->add('sendingEmail', Type\EmailType::class, [
                'label' => $this->translator->trans("E-mail d'envoi", [], 'admin'),
                'required' => false,
                'attr' => [
                    'data-config' => true,
                    'group' => 'col-md-4',
                    'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                ],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Campaign::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
