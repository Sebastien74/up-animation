<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Tab;

use App\Entity\Module\Tab\Tab;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TabType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TabType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * TabType constructor.
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
        $adminName->add($builder, [
            'adminNameGroup' => 'col-md-6',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {
            $builder->add('template', ChoiceType::class, [
                'label' => $this->translator->trans('Affichage', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Onglets horizontaux', [], 'admin') => 'horizontal',
                    $this->translator->trans('Onglets verticaux', [], 'admin') => 'vertical',
                    $this->translator->trans('Accordéon', [], 'admin') => 'accordion',
                ],
                'attr' => ['group' => 'col-md-3'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tab::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
