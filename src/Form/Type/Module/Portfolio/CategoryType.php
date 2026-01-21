<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Portfolio;

use App\Entity\Module\Portfolio\Category;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CategoryType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Category $category */
        $category = $builder->getData();
        $isNew = !$category->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder);

        if (!$isNew) {
            $urls = new WidgetType\UrlsCollectionType($this->coreLocator);
            $urls->add($builder, ['display_seo' => true]);

            if (0 === $category->getLayout()->getZones()->count()) {
                $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
                $mediaRelations->add($builder, [
                    'data_config' => true,
                    'entry_options' => ['onlyMedia' => true],
                ]);

                $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
                $intls->add($builder, [
                    'website' => $options['website'],
                    'fields' => ['title' => 'col-md-8', 'introduction', 'body', 'targetLabel' => 'col-md-4'],
                    'label_fields' => ['targetLabel' => $this->translator->trans('Label du lien de retour', [], 'admin')],
                    'target_config' => false,
                ]);
            }

            $dates = new WidgetType\PublicationDatesType($this->coreLocator);
            $dates->add($builder);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
