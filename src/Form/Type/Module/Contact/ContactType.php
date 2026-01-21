<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Contact;

use App\Entity\Module\Contact\Contact;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ContactType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContactType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * ContactType constructor.
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
            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'fields' => ['title', 'body', 'targetPage' => 'col-md-4', 'targetLabel' => 'col-md-4', 'targetLink' => 'col-md-4', 'placeholder', 'help'],
                'label_fields' => [
                    'targetPage' => $this->translator->trans('Page de contact', [], 'admin'),
                    'targetLabel' => $this->translator->trans('Intitulé page de la contact', [], 'admin'),
                    'targetLink' => $this->translator->trans('E-mail', [], 'admin'),
                    'placeholder' => $this->translator->trans('Numéro de téléphone', [], 'admin'),
                    'help' => $this->translator->trans('Numéro de téléphone (href)', [], 'admin'),
                ],
                'placeholder_fields' => [
                    'targetLabel' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
                    'targetLink' => $this->translator->trans('Saisissez un e-mail', [], 'admin'),
                    'placeholder' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
                    'help' => $this->translator->trans('Saisissez un numéro', [], 'admin'),
                ],
                'excludes_fields' => ['targetStyle', 'newTab', 'externalLink'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
