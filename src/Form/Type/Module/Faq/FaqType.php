<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Faq;

use App\Entity\Module\Faq\Faq;
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
 * FaqType.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class FaqType extends AbstractType
{
    private bool $isInternalUser;

    private TranslatorInterface $translator;

    /**
     * FaqType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-md-9' : 'col-md-6',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {

            $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
            $mediaRelations->add($builder, [
                'data_config' => true,
                'entry_options' => ['onlyMedia' => true],
            ]);

            $builder->add('display', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Affichage', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'choices' => [
                    $this->translator->trans('Tout les volets fermés', [], 'admin') => 'all-closed',
                    $this->translator->trans('Tout les volets ouverts', [], 'admin') => 'all-opened',
                    $this->translator->trans('Premier volet ouvert', [], 'admin') => 'first-opened',
                ],
                'display' => 'search',
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'constraints' => [new Assert\NotBlank()],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'fields' => ['title' => 'col-lg-6', 'subTitle' => 'col-lg-4', 'targetPage' => 'col-lg-4', 'targetLabel' => 'col-lg-4', 'targetStyle' => 'col-lg-4'],
                'target_config' => false,
                'title_force' => true,
            ]);

            $builder->add('disabledMicrodata', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Désactiver les microdatas', [], 'admin'),
                'attr' => ['group' => 'col-md-4 d-flex align-items-end', 'class' => 'w-100'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Faq::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
