<?php

declare(strict_types=1);

namespace App\Form\Type\Core;

use App\Entity\Core\Module;
use App\Form\Widget as WidgetType;
use App\Repository\Security\RoleRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ModuleType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModuleType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ModuleType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly RoleRepository $roleRepository,
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => $isNew ? 'col-md-9' : 'col-md-4', 'slug-internal' => true]);

        $builder->add('role', Type\ChoiceType::class, [
            'required' => true,
            'display' => 'search',
            'label' => $this->translator->trans('Rôle', [], 'admin'),
            'choices' => $this->getRoles(),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => 'col-md-3'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        if (!$isNew) {
            $builder->add('iconClass', WidgetType\FontawesomeType::class, [
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-2',
                ],
                'constraints' => [new Assert\NotBlank()],
            ]);
        }

        $builder->add('inAdvert', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher dans les extensions', [], 'admin'),
            'attr' => ['group' => 'mx-auto col-md-4', 'class' => 'w-100', 'data-config' => true],
        ]);

        if (!$isNew) {
            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['placeholder' => 'col-md-4', 'title' => 'col-md-4', 'subTitle' => 'col-md-4', 'introduction', 'body'],
                'label_fields' => [
                    'placeholder' => $this->translator->trans('Intitulé (Administration)', [], 'admin'),
                    'title' => $this->translator->trans('Intitulé (Nos extensions)', [], 'admin'),
                    'subTitle' => $this->translator->trans('Sous-titre (Nos extensions)', [], 'admin'),
                    'introduction' => $this->translator->trans('Introduction (Nos extensions)', [], 'admin'),
                    'body' => $this->translator->trans('Description (Nos extensions)', [], 'admin'),
                ],
                'placeholder_fields' => [
                    'title' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
                ],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get roles.
     */
    private function getRoles(): array
    {
        $roles = $this->roleRepository->findAll();
        $choices = [];

        foreach ($roles as $role) {
            $choices[$role->getName()] = $role->getName();
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Module::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
