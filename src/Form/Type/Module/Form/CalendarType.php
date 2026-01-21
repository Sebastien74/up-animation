<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\Calendar;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CalendarType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarType extends AbstractType
{
    private TranslatorInterface $translator;

    private bool $isInternalUser;

    /**
     * CalendarType constructor.
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

        if (!$isNew) {
            $builder->add('daysPerPage', Type\IntegerType::class, [
                'label' => $this->translator->trans('Nombre de jour par page', [], 'admin'),
                'attr' => ['data-config' => true, 'group' => 'col-md-3', 'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin')],
                'constraints' => [new Assert\NotBlank()],
            ]);

            $builder->add('frequency', Type\IntegerType::class, [
                'label' => $this->translator->trans('Fréquence', [], 'admin'),
                'attr' => ['data-config' => true, 'group' => 'col-md-3', 'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin')],
                'constraints' => [new Assert\NotBlank()],
            ]);

            $builder->add('minHours', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans("Nombre d'heures minimum avant RDV", [], 'admin'),
                'attr' => ['data-config' => true, 'group' => 'col-md-3', 'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin')],
            ]);

            $builder->add('maxHours', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans("Nombre d'heures maximum avant RDV", [], 'admin'),
                'attr' => ['data-config' => true, 'group' => 'col-md-3', 'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin')],
            ]);

            $builder->add('startHour', Type\TimeType::class, [
                'required' => false,
                'label' => $this->translator->trans('Heure de début', [], 'admin'),
                'attr' => ['data-config' => true, 'group' => 'col-md-2'],
            ]);

            $builder->add('endHour', Type\TimeType::class, [
                'required' => false,
                'label' => $this->translator->trans('Heure de fin', [], 'admin'),
                'attr' => ['data-config' => true, 'group' => 'col-md-2'],
            ]);

            $builder->add('receivingEmails', WidgetType\TagInputType::class, [
                'label' => $this->translator->trans('E-mails de réception', [], 'admin'),
                'required' => false,
                'attr' => [
                    'data-config' => true,
                    'group' => 'col-md-8',
                    'placeholder' => $this->translator->trans('Ajouter des e-mails', [], 'admin'),
                ],
            ]);

            $builder->add('controls', Type\CheckboxType::class, [
                'label' => $this->translator->trans('Activer les boutons de controles ?', [], 'admin'),
                'attr' => ['data-config' => true, 'group' => 'col-md-3'],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'data_config' => true,
                'fields' => ['title' => 'col-md-6', 'subTitle' => 'col-md-6', 'body', 'placeholder' => 'col-12'],
                'label_fields' => [
                    'title' => $this->translator->trans('Objet du mail de reception', [], 'admin'),
                    'subTitle' => $this->translator->trans('Objet du mail de confirmation', [], 'admin'),
                    'body' => $this->translator->trans('Corps du mail de confirmation', [], 'admin'),
                    'placeholder' => $this->translator->trans('Message de remerciement sur le site', [], 'admin'),
                ],
                'placeholder_fields' => [
                    'title' => $this->translator->trans('Saisissez un objet', [], 'admin'),
                    'subTitle' => $this->translator->trans('Saisissez un objet', [], 'admin'),
                    'body' => $this->translator->trans('Saisissez un message', [], 'admin'),
                    'placeholder' => $this->translator->trans('Saisissez un message', [], 'admin'),
                ],
            ]);

            $builder->add('schedules', Type\CollectionType::class, [
                'label' => false,
                'entry_type' => CalendarScheduleType::class,
                'allow_add' => true,
                'prototype' => true,
                'by_reference' => true,
                'entry_options' => ['attr' => ['button' => false, 'icon' => 'fal calendar']],
            ]);

            $builder->add('exceptions', Type\CollectionType::class, [
                'label' => false,
                'entry_type' => CalendarExceptionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'prototype' => true,
                'by_reference' => false,
                'entry_options' => ['attr' => ['group' => 'col-md-4', 'icon' => 'fal concierge-bell']],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Calendar::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
