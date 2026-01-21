<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\StepForm;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * StepFormType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StepFormType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * StepFormType constructor.
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
        /** @var StepForm $form */
        $form = $builder->getData();
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug-internal' => $this->isInternalUser]);

        if (!$isNew) {
            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'data_config' => true,
                'fields' => ['title' => 'col-md-6', 'subTitle' => 'col-md-6', 'body', 'placeholder' => 'col-12'],
                'label_fields' => [
                    'title' => $this->translator->trans('Objet e-mail de reception', [], 'admin'),
                    'subTitle' => $this->translator->trans('Objet e-mail de confirmation', [], 'admin'),
                    'body' => $this->translator->trans("Corps de l'e-mail de confirmation", [], 'admin'),
                    'placeholder' => $this->translator->trans('Message de remerciement sur le site', [], 'admin'),
                ],
                'placeholder_fields' => [
                    'title' => $this->translator->trans('Saisissez un objet', [], 'admin'),
                    'subTitle' => $this->translator->trans('Saisissez un objet', [], 'admin'),
                    'body' => $this->translator->trans('Saisissez un message', [], 'admin'),
                    'placeholder' => $this->translator->trans('Saisissez un message', [], 'admin'),
                ],
            ]);

            $builder->add('configuration', ConfigurationType::class, [
                'label' => false,
                'website' => $options['website'],
                'isNew' => false,
                'entity' => $form->getConfiguration(),
                'excludes' => ['ajax'],
                'attr' => ['data-config' => true],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StepForm::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
