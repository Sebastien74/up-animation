<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Menu;

use App\Entity\Module\Menu\Menu;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MenuType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MenuType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * MenuType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'slug' => true,
            'adminNameGroup' => 'col-12',
            'slugGroup' => 'col-12',
        ]);

        if (!$isNew) {
            $builder->add('template', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Template', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Principal', [], 'admin') => 'main',
                    $this->translator->trans('Latéral', [], 'admin') => 'lateral',
                    $this->translator->trans('Classique', [], 'admin') => 'bootstrap',
                    $this->translator->trans('Pied de page', [], 'admin') => 'footer',
                ],
            ]);

            $builder->add('expand', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Breakpoint', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('SM', [], 'admin') => 'sm',
                    $this->translator->trans('MD', [], 'admin') => 'md',
                    $this->translator->trans('LG', [], 'admin') => 'lg',
                    $this->translator->trans('XL', [], 'admin') => 'xl',
                    $this->translator->trans('XXL', [], 'admin') => 'xxl',
                    $this->translator->trans('XXXL', [], 'admin') => 'xxxl',
                ],
            ]);

            $builder->add('size', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Taille', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Conteneur', [], 'admin') => 'container',
                    $this->translator->trans('Toute la largeur', [], 'admin') => 'container-fluid',
                ],
            ]);

            $builder->add('alignment', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Alignement', [], 'admin'),
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('Gauche', [], 'admin') => 'start',
                    $this->translator->trans('Centré', [], 'admin') => 'center',
                    $this->translator->trans('Droite', [], 'admin') => 'end',
                ],
            ]);

            $builder->add('maxLevel', Type\IntegerType::class, [
                'label' => $this->translator->trans('Nombre maximum de niveau', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                ],
                'required' => false,
            ]);

            $builder->add('main', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Menu principal', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('footer', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Pied de page principal', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('alwaysFixed', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Fixe', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('fixedOnScroll', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Fixe au scroll', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('dropdownHover', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Ouvrir les sous-menus au survol', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('vertical', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Menu vertical', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
