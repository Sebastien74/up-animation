<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CardType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CardType extends AbstractType
{
    private const bool ACTIVE_LARGE = false;
    private TranslatorInterface $translator;

    /**
     * CardType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        if (self::ACTIVE_LARGE) {
            $builder->add('controls', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Mini-fiche large', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);
        }

        $builder->add('backgroundColorType', WidgetType\BackgroundColorSelectType::class, [
            'label' => $this->translator->trans('Couleur de fond', [], 'admin'),
            'attr' => [
                'class' => 'select-icons',
                'group' => 'col-lg-6',
            ],
        ]);

        $builder->add('customTemplate', Type\ChoiceType::class, [
            'required' => false,
            'label' => $this->translator->trans('Template', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'display' => 'search',
            'choices' => [
                $this->translator->trans('Standar', [], 'admin') => 'standard',
                $this->translator->trans('Contenu à droite', [], 'admin') => 'content-right',
                $this->translator->trans('Contenu à gauche', [], 'admin') => 'content-left',
            ],
            'row_attr' => [
                'class' => 'col-lg-6',
                'placeholder' => $this->translator->trans('Template', [], 'admin'),
            ],
        ]);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => ['title' => 'col-md-5', 'subTitle' => 'col-md-5', 'body', 'targetLink' => 'col-md-3 add-title', 'targetPage' => 'col-md-3', 'targetLabel' => 'col-md-3', 'targetStyle' => 'col-md-3', 'newTab' => 'col-md-3'],
            'title_force' => true,
        ]);

        $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelations->add($builder, ['entry_options' => [
            'onlyMedia' => true,
            'sizes' => true,
            'pictogram' => true,
        ]]);

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
