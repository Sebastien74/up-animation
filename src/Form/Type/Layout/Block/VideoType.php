<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * VideoType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class VideoType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * VideoType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => [
                'video' => 'col-12',
                'targetPage' => 'col-md-3',
                'targetLink' => 'col-md-3',
                'targetStyle' => 'col-md-3',
                'targetLabel' => 'col-md-3',
                'newTab' => 'col-md-3',
            ],
            'title_force' => true,
            'label_fields' => [
                'targetLink' => $this->translator->trans('Lien externe', [], 'admin'),
                'video' => $this->translator->trans('Lien externe de la vidéo', [], 'admin'),
            ],
            'placeholder_fields' => [
                'placeholder' => $this->translator->trans('Saisissez le code URL', [], 'admin'),
            ],
            'help_fields' => ['placeholder' => $this->translator->trans('Youtube, Vimeo, Dailymotion', [], 'admin')],
        ]);

        $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelations->add($builder, [
            'entry_options' => [
                'onlyMedia' => true,
                'video' => true,
            ],
        ]);

        $builder->add('autoplay', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Lecture automatique', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('playInHover', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Lecture au survol', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('controls', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher les boutons de contrôle', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('soundControls', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher le bouton de contrôle du son', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('asLoop', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('En boucle', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $radiusType = new WidgetType\RadiusType($this->coreLocator);
        $radiusType->add($builder);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_back' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
