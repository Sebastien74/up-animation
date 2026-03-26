<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Slider;

use App\Entity\Core\Website;
use App\Entity\Module\Slider\Slider;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SliderType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SliderType extends AbstractType
{
    private const array TEMPLATES_ACTIVATION = [
        'bootstrap' => true,
        'banner' => true,
        'splide' => true,
        'two-columns' => true,
    ];

    private TranslatorInterface $translator;
    private string $projectDir;
    private bool $isInternalUser;

    /**
     * SliderType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->projectDir = $this->coreLocator->projectDir();
        $this->isInternalUser = $this->coreLocator->authorizationChecker()->isGranted('ROLE_INTERNAL');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();
        $websiteModel = $this->coreLocator->website();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'slug-internal' => $this->isInternalUser,
            'adminNameGroup' => $isNew ? 'col-md-9' : 'col-sm-9',
        ]);

        $builder->add('template', Type\ChoiceType::class, [
            'label' => $this->translator->trans('Type de carrousel', [], 'admin'),
            'display' => 'search',
            'attr' => [
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'data-config' => true
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'choices' => $this->getTemplates($options['website']),
        ]);

        if (!$isNew && $this->isInternalUser) {

            $builder->add('intervalDuration', Type\IntegerType::class, [
                'label' => $this->translator->trans('Intervalle en millisecondes', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('effect', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Effet', [], 'admin'),
                'display' => 'search',
                'attr' => [
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'choices' => [
                    'Fade' => 'fade',
                    'Slide' => 'slide',
                ],
            ]);

            $builder->add('backgroundColor', WidgetType\BackgroundColorSelectType::class, [
                'attr' => [
                    'class' => 'col-12 select-icons',
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('itemsPerSlide', Type\IntegerType::class, [
                'label' => $this->translator->trans("Nombre d'images par slide (Ordinateur)", [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('itemsPerSlideMiniPC', Type\IntegerType::class, [
                'label' => $this->translator->trans("Nombre d'images par slide (Mini PC)", [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('itemsPerSlideTablet', Type\IntegerType::class, [
                'label' => $this->translator->trans("Nombre d'images par slide (Tablette)", [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('itemsPerSlideMobile', Type\IntegerType::class, [
                'label' => $this->translator->trans("Nombre d'images par slide (Mobile)", [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('offsetDesktop', Type\IntegerType::class, [
                'label' => $this->translator->trans('Décalage en pixel (Ordinateur)', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('offsetMiniPC', Type\IntegerType::class, [
                'label' => $this->translator->trans('Décalage en pixel (Mini PC)', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('offsetTablet', Type\IntegerType::class, [
                'label' => $this->translator->trans('Décalage en pixel (Tablette)', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('offsetMobile', Type\IntegerType::class, [
                'label' => $this->translator->trans('Décalage en pixel (Mobile)', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),

                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('focus', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Focus (Ordinateur)', [], 'admin'),
                'display' => 'search',
                'attr' => ['data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'choices' => [
                    $this->translator->trans('À gauche', [], 'admin') => 'left',
                    $this->translator->trans('Centré', [], 'admin') => 'center',
                ],
            ]);

            $builder->add('focusMiniPC', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Focus (Mini PC)', [], 'admin'),
                'display' => 'search',
                'attr' => ['data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'choices' => [
                    $this->translator->trans('À gauche', [], 'admin') => 'left',
                    $this->translator->trans('Centré', [], 'admin') => 'center',
                ],
            ]);

            $builder->add('focusTablet', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Focus (Tablette)', [], 'admin'),
                'display' => 'search',
                'attr' => ['data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'choices' => [
                    $this->translator->trans('À gauche', [], 'admin') => 'left',
                    $this->translator->trans('Centré', [], 'admin') => 'center',
                ],
            ]);

            $builder->add('focusMobile', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Focus (Mobile)', [], 'admin'),
                'display' => 'search',
                'attr' => ['data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                'choices' => [
                    $this->translator->trans('À gauche', [], 'admin') => 'left',
                    $this->translator->trans('Centré', [], 'admin') => 'center',
                ],
            ]);

            $builder->add('indicators', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Afficher les points de navigation', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3 d-flex align-items-end'],
            ]);

            $builder->add('autoplay', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Lecture automatique', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('pause', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Pause au survol', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('popup', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Afficher popup au clic des images', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('standardizeMedia', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Uniformiser la hauteur des médias', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('progress', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Barre de progression', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('control', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Afficher les flèches de navigation', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('thumbnails', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Afficher les vignettes', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100', 'data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('arrowColor', WidgetType\ButtonColorType::class, [
                'label' => $this->translator->trans('Couleur des flèches de navigation', [], 'admin'),
                'cta' => false,
                'ctaColors' => false,
                'gradientColors' => $websiteModel->configuration->customModules->gradientColors,
                'glassColors' => $websiteModel->configuration->customModules->glassColors,
                'attr' => [
                    'class' => 'col-12 select-icons',
                    'data-config' => true
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('arrowAlignment', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Alignement des flèches de navigation', [], 'admin'),
                'display' => 'search',
                'required' => false,
                'choices' => [
                    $this->translator->trans('En haut à gauche', [], 'admin') => 'top-start',
                    $this->translator->trans('En haut à droite', [], 'admin') => 'top-end',
                    $this->translator->trans('En haut à centré', [], 'admin') => 'top-center',
                    $this->translator->trans('En bas à gauche', [], 'admin') => 'bottom-start',
                    $this->translator->trans('En bas à droite', [], 'admin') => 'bottom-end',
                    $this->translator->trans('En bas à centré', [], 'admin') => 'bottom-center',
                ],
                'attr' => ['data-config' => true],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get front templates.
     */
    private function getTemplates(Website $website): array
    {
        $finder = Finder::create();
        $templateDir = $website->getConfiguration()->getTemplate();
        $templates = [];
        $frontDir = $this->projectDir.'/templates/front/'.$templateDir.'/actions/slider/template';
        $frontDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $frontDir);
        $finder->files()->in($frontDir)->depth([0]);
        foreach ($finder as $file) {
            $code = str_replace('.html.twig', '', $file->getFilename());
            $active = self::TEMPLATES_ACTIVATION[$code] ?? true;
            if ($active) {
                $templates[$this->getTemplateName($code)] = $code;
                if ('two-columns' === $code) {
                    $templates[$this->getTemplateName('two-columns-text-right')] = 'two-columns-text-right';
                }
            }
        }

        return $templates;
    }

    /**
     * To get template name.
     */
    private function getTemplateName(string $code): string
    {
        $names = [
            'bootstrap' => $this->translator->trans('Classique', [], 'admin'),
            'splide' => $this->translator->trans('Multiple', [], 'admin'),
            'banner' => $this->translator->trans('Bannière', [], 'admin'),
            'main-home' => $this->translator->trans("Principal page d'accueil", [], 'admin'),
            'two-columns' => $this->translator->trans('Deux colonnes (Contenu à gauche)', [], 'admin'),
            'two-columns-text-right' => $this->translator->trans('Deux colonnes (Contenu à droite)', [], 'admin'),
        ];

        return !empty($names[$code]) ? $names[$code] : ucfirst($code);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Slider::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
