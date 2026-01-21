<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Core\Module;
use App\Entity\Layout\Page;
use App\Entity\Module\Form\Configuration;
use App\Entity\Security\User;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private ?User $user;
    private bool $isInternalUser;

    /**
     * ConfigurationType constructor.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $this->user && in_array('ROLE_INTERNAL', $this->user->getRoles());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Configuration $configuration */
        $configuration = !empty($options['entity']) ? $options['entity'] : null;

        $builder->add('receivingEmails', WidgetType\TagInputType::class, [
            'label' => $this->translator->trans('E-mails de réception', [], 'admin'),
            'required' => false,
            'attr' => [
                'group' => 'col-md-8',
                'placeholder' => $this->translator->trans('Ajouter des e-mails', [], 'admin'),
            ],
        ]);

        $builder->add('sendingEmail', Type\EmailType::class, [
            'label' => $this->translator->trans("E-mail d'envoi", [], 'admin'),
            'attr' => [
                'group' => 'col-md-4',
                'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
        ]);

        if ($this->isInternalUser) {
            $builder->add('maxShipments', Type\IntegerType::class, [
                'label' => $this->translator->trans('Maximum de soumissions', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'group' => 'col-md-2',
                    'data-config' => true,
                ],
            ]);

            $builder->add('pageRedirection', EntityType::class, [
                'required' => false,
                'label' => $this->translator->trans('Page de redirection', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'group' => 'col-md-2 allow-clear',
                ],
                'class' => Page::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->leftJoin('p.urls', 'u')
                        ->andWhere('p.deletable = :deletable')
                        ->andWhere('u.online = :online')
                        ->setParameter('deletable', true)
                        ->setParameter('online', true)
                        ->addSelect('u')
                        ->orderBy('p.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'display' => 'search',
            ]);

            $dates = new WidgetType\PublicationDatesType($this->coreLocator);
            $dates->add($builder, [
                'entity' => $configuration,
                'startLabel' => $this->translator->trans('Afficher à partir du', [], 'admin'),
                'endLabel' => $this->translator->trans('Retirer à partir du', [], 'admin'),
            ]);

            $builder->add('dbRegistration', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Enregistrer les contacts', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('attachmentsInMail', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Fichiers en pièces-jointes du mail', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('uniqueContact', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Un seul envoi de mail possible', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('thanksModal', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher modal de remerciement', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('thanksPage', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Rediriger vers la page remerciement', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);
        }

        $builder->add('confirmEmail', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Envoyer un e-mail de confirmation', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
        ]);

        if ($this->isInternalUser) {
            $builder->add('recaptcha', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer le recaptcha', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);

            $builder->add('dynamic', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer les champs dynamiques', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);

            $module = $this->entityManager->getRepository(Module::class)->findOneBy(['slug' => 'form-calendar']);
            $moduleActive = $this->entityManager->getRepository(\App\Entity\Core\Configuration::class)->moduleExist($options['website'], $module);
            if ($moduleActive && $this->user && in_array('ROLE_FORM_CALENDAR', $this->user->getRoles())) {
                $builder->add('calendarsActive', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Activer les calendriers', [], 'admin'),
                    'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
                ]);
            }

            if (!$options['excludes'] || !['ajax', $options['excludes']]) {
                $builder->add('ajax', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Soumission en ajax', [], 'admin'),
                    'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
                ]);
            }

            $builder->add('floatingLabels', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Labels dans les champs', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
            'website' => null,
            'isNew' => false,
            'entity' => null,
            'excludes' => [],
            'translation_domain' => 'admin',
        ]);
    }
}
