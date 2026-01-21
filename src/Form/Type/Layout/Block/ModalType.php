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
 * ModalType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModalType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ModalType constructor.
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
            'label_fields' => ['placeholder' => $this->translator->trans('Intitulé du bouton', [], 'admin')],
            'fields' => ['title' => 'col-md-4', 'subTitle' => 'col-md-4', 'placeholder' => 'col-md-4', 'introduction', 'body'],
        ]);

        $builder->add('timer', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Délais avant apparition', [], 'admin'),
            'help' => $this->translator->trans("En secondes - S'affichera automatiquement sans le bouton", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin'),
            ],
        ]);

        $builder->add('width', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Délais du cookie', [], 'admin'),
            'help' => $this->translator->trans('En jours - Réaffichera la modal après ce délais', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin'),
            ],
        ]);

        $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelations->add($builder, [
            'entry_options' => [
                'onlyMedia' => true,
            ],
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
