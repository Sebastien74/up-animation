<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\BlockType;
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
 * BlockTypeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockTypeType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * BlockTypeType constructor.
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
        $adminName->add($builder, [
            'slug-force' => true,
            'adminNameGroup' => $isNew ? 'col-md-6' : 'col-md-3',
            'slugGroup' => $isNew ? 'col-md-6' : 'col-md-3',
        ]);

        $builder->add('iconClass', WidgetType\FontawesomeType::class, [
            'required' => true,
            'attr' => [
                'class' => 'select-icons',
                'group' => $isNew ? 'col-md-4' : 'col-md-3',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('category', Type\TextType::class, [
            'label' => $this->translator->trans('Catégorie', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'group' => $isNew ? 'col-md-4' : 'col-md-3',
            ],
        ]);

        $builder->add('role', Type\ChoiceType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->translator->trans('Rôle', [], 'admin'),
            'choices' => $this->getRoles(),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => $isNew ? 'col-md-4' : 'col-md-3'],
        ]);

        $builder->add('dropdown', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Ajouter à la zone non prioritaire', [], 'admin'),
            'attr' => ['group' => $isNew ? 'col-md-4' : 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $builder->add('editable', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Éditable', [], 'admin'),
            'attr' => ['group' => $isNew ? 'col-md-4' : 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $builder->add('inAdvert', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher aux extensions', [], 'admin'),
            'attr' => ['group' => $isNew ? 'col-md-4' : 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
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
            'data_class' => BlockType::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
