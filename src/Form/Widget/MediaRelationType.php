<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Media\Category;
use App\Entity\Media\MediaRelationIntl;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MediaRelationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaRelationType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?WebsiteModel $website = null;

    /**
     * MediaRelationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $builder->getData();
        $this->website = $this->coreLocator->website();

        $builder->add('media', MediaType::class, [
            'label' => false,
            'titlePosition' => $options['titlePosition'],
            'hideHover' => $options['hideHover'],
            'quality' => $options['quality'],
            'copyright' => $options['copyright'],
            'categories' => $options['categories'],
            'screen' => $options['screen'],
            'video' => $options['video'],
            'onlyMp3' => $options['onlyMp3'],
            'onlyMedia' => $options['onlyMedia'],
            'dataHeight' => $options['dataHeight'],
        ]);

        if ($options['video']) {

            $builder->add('title', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Titre de la vidéo', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Veuillez saisir un titre', [], 'admin'),
                    'data-help-alert' => 'warning',
                    'group' => 'hide-ai',
                ],
                'help' => $this->translator->trans("S'il ne s'agit pas d'une vidéo décorative, il est nécessaire d'ajouter un titre pour rendre le contenu accessible au personnes mal entendantes.", [], 'admin')
            ]);

            $builder->add('body', Type\TextareaType::class, [
                'required' => false,
                'label' => $this->translator->trans('Description de la vidéo', [], 'admin'),
                'editor' => 'basic',
                'attr' => [
                    'placeholder' => $this->translator->trans('Veuillez saisir une description', [], 'admin'),
                    'data-help-alert' => 'warning',
                    'group' => 'hide-ai',
                ],
                'help' => $this->translator->trans("S'il ne s'agit pas d'une vidéo décorative, il est nécessaire d'ajouter une description pour rendre le contenu accessible au personnes mal entendantes.", [], 'admin')
            ]);
        }

        if (!$options['onlyMedia'] || $options['sizes']) {
            $adminName = new MediaSizesType($this->coreLocator);
            $adminName->add($builder);
        }

        if (!$options['onlyMedia']) {
            $builder->add('downloadable', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Téléchargeable', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('popup', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher popup au clic', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            $builder->add('main', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Image principale', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);

            if ($options['header']) {
                $builder->add('header', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans("Image d'entête", [], 'admin'),
                    'attr' => ['class' => 'w-100'],
                ]);
            }

            $radiusType = new RadiusType($this->coreLocator);
            $radiusType->add($builder, ['group' => 'col-12']);

            if ($data && method_exists($data, 'setBackgroundColor')) {
                $builder->add('backgroundColor', BackgroundColorSelectType::class, [
                    'attr' => [
                        'group' => 'col-12',
                        'class' => ' select-icons',
                        'data-config' => true,
                    ],
                ]);
            }

            if (!empty($options['fields']['intl'])) {
                $options['label_fields']['intl']['placeholder'] = empty($options['label_fields']['placeholder']) ? $this->translator->trans("Balise alt de l'image", [], 'admin') : $options['label_fields']['placeholder'];
                $options['placeholder_fields']['intl']['placeholder'] = empty($options['placeholder_fields']['placeholder']) ? $this->translator->trans('Saisissez un titre', [], 'admin') : $options['placeholder_fields']['placeholder'];
                $builder->add('intl', IntlType::class, [
                    'label' => false,
                    'title_force' => $options['intlTitleForce'],
                    'data_class' => MediaRelationIntl::class,
                    'fields' => $options['fields']['intl'],
                    'excludes_fields' => !empty($options['excludes_fields']['intl']) ? $options['excludes_fields']['intl'] : [],
                    'label_fields' => !empty($options['label_fields']['intl']) ? $options['label_fields']['intl'] : [],
                    'placeholder_fields' => !empty($options['placeholder_fields']['intl']) ? $options['placeholder_fields']['intl'] : [],
                    'website' => $options['website'],
                ]);
            }

            foreach ($options['fields'] as $key => $field) {
                if (is_string($field)) {
                    $getter = 'get'.ucfirst($field);
                    if (method_exists($this, $getter)) {
                        $this->$getter($builder, $field);
                    }
                }
            }
        }

        if (!empty($options['fields']['intl']) && $options['forceIntl']) {
            $builder->add('intl', IntlType::class, [
                'label' => false,
                'title_force' => $options['intlTitleForce'],
                'data_class' => MediaRelationIntl::class,
                'fields' => $options['fields']['intl'],
                'excludes_fields' => !empty($options['excludes_fields']['intl']) ? $options['excludes_fields']['intl'] : [],
                'label_fields' => !empty($options['label_fields']['intl']) ? $options['label_fields']['intl'] : [],
                'placeholder_fields' => !empty($options['placeholder_fields']['intl']) ? $options['placeholder_fields']['intl'] : [],
                'website' => $options['website'],
            ]);
        }

        if ($options['category']) {
            $categories = $this->coreLocator->em()->getRepository(Category::class)->findBy(['website' => $this->website->entity], ['adminName' => 'ASC']);
            $choices = [];
            foreach ($categories as $category) {
                $choices[$category->getAdminName()] = $category->getSlug();
            }
            $builder->add('categorySlug', Type\ChoiceType::class, [
                'required' => false,
                'display' => 'search',
                'choices' => $choices,
                'label' => $this->translator->trans('Catégorie', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-12'],
            ]);
        }

        if ($options['pictogram']) {
            $builder->add('pictogram', PictogramType::class, [
                'attr' => [
                    'group' => 'col-md-12',
                    'class' => 'select-icons img-pictograms',
                ],
            ]);
        }

        if ($options['pictogramSizes']) {

            $builder->add('pictogramMaxWidth', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Largeur (px) du picto', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez une largeur', [], 'admin'),
                    'tabSize' => !empty($options['tabSize']) ? $options['tabSize'] : '12',
                    'group' => 'col-md-12',
                ],
            ]);

            $builder->add('pictogramMaxHeight', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Hauteur (px) du picto', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez une hauteur', [], 'admin'),
                    'group' => 'col-md-12',
                ],
            ]);
        }

        if ($options['active']) {
            $builder->add('active', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);
        }

        if ($options['save']) {
            $this->getSubmit($builder);
        }
    }

    /**
     * Submit field.
     */
    private function getSubmit(FormBuilderInterface $builder): void
    {
        $saveOptions = [
            'btn_save' => true,
            'force' => true,
            'class' => 'btn-info-darken ajax-post inner-preloader-btn w-100 text-white standard medias',
        ];
        $save = new SubmitType($this->coreLocator);
        $save->add($builder, $saveOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'label' => false,
            'website' => null,
            'screen' => false,
            'forceIntl' => false,
            'video' => false,
            'titlePosition' => false,
            'hideHover' => false,
            'quality' => false,
            'rider' => false,
            'copyright' => false,
            'categories' => false,
            'pictogram' => false,
            'pictogramSizes' => false,
            'onlyOne' => false,
            'onlyMp3' => false,
            'onlyMedia' => false,
            'onlyLocaleMedias' => false,
            'header' => false,
            'sizes' => false,
            'active' => false,
            'save' => false,
            'category' => false,
            'intlTitleForce' => true,
            'dataHeight' => null,
            'fields' => [],
            'label_fields' => [],
            'placeholder_fields' => [],
            'excludes_fields' => [],
            'required_fields' => [],
            'translation_domain' => 'admin',
        ]);
    }
}
