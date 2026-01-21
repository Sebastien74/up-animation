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
 * TextType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TextType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * TextType constructor.
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
            'fields' => ['introduction', 'body', 'placeholder' => 'col-12 accessibility-table warning-help d-none'],
            'label_fields' => ['placeholder' => $this->translator->trans("Description du tableau", [], 'admin')],
            'placeholder_fields' => ['placeholder' => $this->translator->trans('Saisissez un description', [], 'admin')],
            'help_fields' => ['placeholder' => $this->translator->trans("A compléter pour l'accessibilité", [], 'admin')],
        ]);

        $configs = new WidgetType\ContentConfigType($this->coreLocator);
        $configs->add($builder, [
            'website' => $options['website'],
            'fields' => [
                'fontWeight' => 'col-md-3',
                'color' => 'col-md-3',
                'fontSize' => 'col-md-3',
                'fontWeightSecondary' => 'col-md-3',
                'italic',
                'uppercase'
            ],
            'labels' => [
                'fontWeight' => $this->translator->trans('Gras du contenu', [], 'admin'),
                'color' => $this->translator->trans("Couleur de l'introduction", [], 'admin'),
                'fontSize' => $this->translator->trans("Taille de la police de l'introduction", [], 'admin'),
                'fontWeightSecondary' => $this->translator->trans("Gras de l'introduction", [], 'admin'),
                'italic' => $this->translator->trans("Introduction en italique", [], 'admin'),
            ],
        ]);

        $builder->add('controls', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Liste à puces checkbox', [], 'admin'),
            'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
        ]);

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
