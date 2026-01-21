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
 * CollapseType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CollapseType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CollapseType constructor.
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
            'title_force' => true,
            'fields' => [
                'titleForce' => 'col-md-2', 'title' => 'col-md-4', 'placeholder' => 'col-md-3', 'targetStyle' => 'col-md-3', 'introduction', 'body',
            ],
            'label_fields' => [
                'title' => $this->translator->trans('Titre', [], 'admin'),
                'placeholder' => $this->translator->trans('Intitulé du bouton', [], 'admin'),
            ],
            'placeholder_fields' => ['title' => $this->translator->trans('Saisissez un intitulé', [], 'admin')],
            'excludes_fields' => ['newTab', 'externalLink'],
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
