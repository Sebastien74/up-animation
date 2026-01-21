<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MediaType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * MediaType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Website $website */
        $website = $options['website'];
        $configuration = $website->getConfiguration();
        //        $haveSecondaryMedia = $configuration->isMediasSecondary();
        //        $groupClass = $haveSecondaryMedia ? 'col-md-6' : 'col-md-6';

        $builder->add('template', WidgetType\TemplateBlockType::class);

        $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelations->add($builder, [
            'entry_options' => [
                'copyright' => true,
                'category' => $website->getConfiguration()->isMediasCategoriesStatus(),
                'titlePosition' => true,
                'pictogram' => true,
                'pictogramSizes' => true,
                'intlTitleForce' => false,
                'hideHover' => true,
                'fields' => [
                    'intl' => ['title' => 'col-md-6', 'placeholder' => 'col-md-6', 'introduction', 'targetStyle' => 'col-md-6', 'targetLink' => 'col-md-6', 'targetPage' => 'col-md-6', 'targetLabel' => 'col-md-6'],
                ],
                'label_fields' => [
                    'intl' => [
                        'title' => $this->translator->trans('Titre', [], 'admin'),
                        'placeholder' => $this->translator->trans('ALT', [], 'admin'),
                        'introduction' => $this->translator->trans('Description', [], 'admin'),
                    ],
                ],
                'placeholder_fields' => [
                    'intl' => [
                        'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                        'introduction' => $this->translator->trans('Saisissez une description', [], 'admin'),
                    ],
                ],
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
