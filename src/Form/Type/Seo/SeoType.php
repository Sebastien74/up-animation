<?php

declare(strict_types=1);

namespace App\Form\Type\Seo;

use App\Entity\Core\Website;
use App\Entity\Media\MediaRelation;
use App\Entity\Seo\Seo;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SeoType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeoType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SeoType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Website $website */
        $website = $options['website'];

        $builder->add('metaTitle', Type\TextType::class, [
            'label' => $this->translator->trans('Méta titre', [], 'admin'),
            'counter' => 55,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'class' => 'col-12 meta-title refer-code',
            ],
            'required' => false,
        ]);

        $builder->add('metaTitleSecond', Type\TextType::class, [
            'label' => $this->translator->trans('Méta titre (après le tiret)', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'class' => 'col-12 meta-title-second',
            ],
            'required' => false,
        ]);

        $builder->add('metaDescription', Type\TextareaType::class, [
            'label' => $this->translator->trans('Méta description', [], 'admin'),
            'counter' => 155,
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une description', [], 'admin'),
                'class' => 'col-12 meta-description',
            ],
            'required' => false,
        ]);

        $builder->add('metaCanonical', Type\TextType::class, [
            'label' => $this->translator->trans('URI Canonique', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez une URI', [], 'admin'),
                'class' => 'col-12 meta-canonical',
            ],
            'help' => $this->translator->trans('Sans le nom de domaine Ex: /my-page-url', [], 'admin'),
            'required' => false,
        ]);

        $builder->add('breadcrumbTitle', Type\TextType::class, [
            'label' => $this->translator->trans("Titre du fil d'ariane", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
            ],
            'required' => false,
        ]);

        $builder->add('noAfterDash', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'app',
            'label' => $this->translator->trans('Désactiver après tiret', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12'],
        ]);

        $fields = ['code', 'hideInSitemap' => 'col-md-6', 'online', 'asIndex' => 'col-md-6'];
        if ($options['have_index_page']) {
            $fields[] = 'indexPage';
        }
        $builder->add('url', WidgetType\UrlType::class, [
            'fields' => $fields,
        ]);

        $builder->add('metaOgTitle', Type\TextType::class, [
            'label' => $this->translator->trans('Méta titre', [], 'admin'),
            'counter' => 55,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                'class' => 'col-12 meta-og-title',
            ],
            'help' => $this->translator->trans('La méta "après tiret" ne sera pas prise en compte', [], 'admin'),
            'required' => false,
        ]);

        $builder->add('metaOgDescription', Type\TextareaType::class, [
            'label' => $this->translator->trans('Méta description', [], 'admin'),
            'counter' => 155,
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une description', [], 'admin'),
                'class' => 'col-12 meta-og-description',
            ],
            'required' => false,
        ]);

        $builder->add('metaOgTwitterCard', Type\TextType::class, [
            'label' => $this->translator->trans('OG Twitter card', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le type de card', [], 'admin'),
            ],
            'required' => false,
        ]);

        $builder->add('metaOgTwitterHandle', Type\TextType::class, [
            'label' => $this->translator->trans('OG Twitter handle', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le type de handle', [], 'admin'),
            ],
            'required' => false,
        ]);

        $builder->add('mediaRelation', WidgetType\MediaRelationType::class, [
            'onlyMedia' => true,
            'data_class' => MediaRelation::class,
        ]);

        $builder->add('footerDescription', Type\TextareaType::class, [
            'label' => $this->translator->trans('Description pied de page', [], 'admin'),
            'editor' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une description', [], 'admin'),
                'class' => 'col-12 footer-description',
            ],
            'required' => false,
        ]);

        $builder->add('author', Type\TextType::class, [
            'label' => $this->translator->trans('Auteur', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un auteur', [], 'admin'),
            ],
            'required' => false,
        ]);

        if ($website->getSeoConfiguration()->isMicroData()) {
            $builder->add('authorType', Type\TextType::class, [
                'label' => $this->translator->trans("Type d'auteur", [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un type', [], 'admin')
            ],
            'row_attr' => ['class' => 'col-12'],
                'required' => false,
            ]);

            $builder->add('metadata', Type\TextareaType::class, [
                'label' => $this->translator->trans('Script', [], 'admin'),
                'editor' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un script', [], 'admin'),
                    
                    'class' => 'col-12 metadata'
            ],
            'row_attr' => ['class' => 'col-12'],
                'help' => $this->translator->trans('Sans la balise <code>&lt;script></code>', [], 'admin'),
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seo::class,
            'website' => null,
            'have_index_page' => false,
            'translation_domain' => 'admin',
        ]);
    }
}
