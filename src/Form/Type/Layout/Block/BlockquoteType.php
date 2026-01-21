<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BlockquoteType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockquoteType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * BlockquoteType constructor.
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
            'fields' => ['introduction', 'targetLink', 'author' => 'col-md-3'],
            'title_force' => true,
            'target_config' => false,
            'label_fields' => [
                'introduction' => $this->translator->trans('Citation', [], 'admin'),
                'targetLink' => $this->translator->trans('Lien de la source', [], 'admin'),
            ],
        ]);

        $configs = new WidgetType\ContentConfigType($this->coreLocator);
        $configs->add($builder, [
            'website' => $options['website'],
            'fields' => ['color' => 'col-md-3', 'fontWeight' => 'col-md-3', 'fontSize' => 'col-md-3', 'italic', 'uppercase'],
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
