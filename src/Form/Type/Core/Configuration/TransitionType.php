<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Configuration;

use App\Entity\Core\Transition;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TransitionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TransitionType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * TransitionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'slug' => true,
            'adminNameGroup' => 'col-12',
            'slugGroup' => 'col-md-6',
        ]);

        $builder->add('section', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Section', [], 'admin'),
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une section', [], 'admin')],
            'row_attr' => ['class' => 'col-md-6'],
        ]);

        $builder->add('laxPreset', WidgetType\LaxEffectType::class, [
            'attr' => ['data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin')],
        ]);

        $builder->add('aosEffect', WidgetType\AosEffectType::class, [
            'attr' => ['data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin')],
        ]);

        $builder->add('animateEffect', WidgetType\AnimateCssType::class, [
            'attr' => ['data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin')],
        ]);

        $builder->add('parameters', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Personnalisation', [], 'admin'),
            'attr' => ['placeholder' => $this->translator->trans('Saisissez vos valeurs', [], 'admin')],
        ]);

        $builder->add('duration', WidgetType\EffectDurationType::class, [
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin')],
            'row_attr' => ['class' => 'col-md-4'],
        ]);

        $builder->add('delay', WidgetType\EffectDelayType::class, [
            'attr' => ['placeholder' => $this->translator->trans('Saisissez un délai', [], 'admin')],
            'row_attr' => ['class' => 'col-md-4'],
        ]);

        $builder->add('offsetData', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Offset', [], 'admin'),
            'attr' => ['placeholder' => $this->translator->trans('Saisissez un offset', [], 'admin')],
            'row_attr' => ['class' => 'col-md-4'],
        ]);

        $builder->add('active', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Actif pour les sélecteurs', [], 'admin'),
            'attr' => ['class' => 'w-100'],
        ]);

        $builder->add('activeForBlock', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Actif pour les blocks', [], 'admin'),
            'attr' => ['class' => 'w-100'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transition::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
