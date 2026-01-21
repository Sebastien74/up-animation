<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Configuration;

use App\Entity\Core\Color;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ColorType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColorType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ColorType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('adminName', Type\TextType::class, [
            'label' => $this->translator->trans('Nom de la couleur', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('color', Type\TextType::class, [
            'label' => $this->translator->trans('Couleur', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une couleur', [], 'admin'),
                'class' => 'colorpicker',
            ],
        ]);

        $builder->add('slug', Type\TextType::class, [
            'label' => $this->translator->trans('Code CSS', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un code', [], 'admin'),
            ],
        ]);

        $builder->add('category', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Catégorie', [], 'admin'),
            'display' => 'search',
            'choices' => [
                $this->translator->trans('Couleur de fond', [], 'admin') => 'background',
                $this->translator->trans('Bouton', [], 'admin') => 'button',
                $this->translator->trans('Couleur', [], 'admin') => 'color',
                $this->translator->trans('Favicon', [], 'admin') => 'favicon',
                $this->translator->trans('Alerte', [], 'admin') => 'alert',
            ],
            'attr' => [
                'placeholder' => $this->translator->trans('Séléctionnez', [], 'admin'),
            ],
        ]);

        $builder->add('active', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Actif', [], 'admin'),
            'attr' => ['class' => 'w-100'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Color::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
