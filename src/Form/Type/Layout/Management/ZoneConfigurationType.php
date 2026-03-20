<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Core\Configuration;
use App\Entity\Layout\Zone;
use App\Form\Widget as WidgetType;
use App\Form\Widget\RadiusType;
use App\Form\Widget\WebsiteLocalesType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ZoneConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZoneConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * ZoneConfigurationType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface  $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    )
    {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Zone $zone */
        $zone = $builder->getData();
        $website = $options['website'];

        /** @var Configuration $configuration */
        $configuration = $options['website']->getConfiguration();
        $multiLocales = count($configuration->getAllLocales()) > 1;

        $builder->add('customClass', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Classes personnalisées', [], 'admin'),
            'attr' => ['class' => 'col-12 input-css',
                'placeholder' => $this->translator->trans('Éditer', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-8'],
        ]);

        $builder->add('customId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Id personnalisé', [], 'admin'),
            'attr' => ['placeholder' => $this->translator->trans('Éditer', [],
                'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
        ]);

        $builder->add('idAsAnchor', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'app',
            'label' => $this->translator->trans('Id pour ancre', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $margins = new MarginType($this->coreLocator);
        $margins->add($builder);

        $transitions = new TransitionType($this->coreLocator);
        $transitions->add($builder, ['website' => $website]);

        $builder->add('alignment', AlignmentType::class, [

            'row_attr' => ['class' => $zone->isFullSize() ? 'col-12' : 'col-md-6'],
        ]);

        if (!$zone->isFullSize()) {
            $choices = [];
            $limit = 11;
            for ($i = 1; $i <= $limit; ++$i) {
                $choices[$i] = $i;
            }
            $builder->add('containerSize', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Taille du conteneur', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'choices' => $choices,
                'display' => 'search',
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            ]);
        }

        $builder->add('fullSize', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'app',
            'label' => $this->translator->trans('Étendre', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $builder->add('hideMobile', HideType::class, [
            'label' => $this->translator->trans('Cacher la zone sur mobile', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $builder->add('hideTablet', HideType::class, [
            'label' => $this->translator->trans('Cacher la zone sur tablette', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $builder->add('hideMiniPc', HideType::class, [
            'label' => $this->translator->trans('Cacher la zone sur mini PC', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $builder->add('hideDesktop', HideType::class, [
            'label' => $this->translator->trans('Cacher la zone sur PC', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
        ]);

        $builder->add('hide', HideType::class, [
            'label' => $this->translator->trans('Cacher la zone', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $centerColLabel = 1 === $zone->getCols()->count()
            ? $this->translator->trans('Centrer la colonne', [], 'admin')
            : $this->translator->trans('Centrer les colonnes', [], 'admin');
        $builder->add('centerCol', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'app',
            'label' => $centerColLabel,
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $colToRightLabel = 1 === $zone->getCols()->count()
            ? $this->translator->trans('Aligner la colonne à droite', [], 'admin')
            : $this->translator->trans('Aligner les colonnes à droite', [], 'admin');
        $builder->add('colToRight', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'app',
            'label' => $colToRightLabel,
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        $colToRightLabel = 1 === $zone->getCols()->count()
            ? $this->translator->trans('Aligner la colonne en bas', [], 'admin')
            : $this->translator->trans('Aligner les colonnes en bas', [], 'admin');
        $builder->add('colToEnd', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'app',
            'label' => $colToRightLabel,
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
        ]);

        if ($zone->getCols()->count() > 1) {
            $builder->add('standardizeElements', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Uniformiser la largeur des colonnes', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            ]);
        }

        $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelations->add($builder, ['entry_options' => ['onlyMedia' => true, 'dataHeight' => 100]]);

        $builder->add('standardizeMedia', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'app',
            'label' => $this->translator->trans('Uniformiser la hauteur des médias', [], 'admin'),
            'attr' => ['class' => 'col-12 w-100'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
        ]);

        $radiusType = new RadiusType($this->coreLocator);
        $radiusType->add($builder);

        if ($this->isInternalUser) {
            $builder->add('backgroundFixed', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Arrière-plan fixe ?', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('backgroundParallax', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'app',
                'label' => $this->translator->trans('Arrière-plan avec effet de parallax ?', [], 'admin'),
                'attr' => ['class' => 'col-12 w-100'],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            //			$builder->add('titlePosition', Type\ChoiceType::class, [
            //				'required' => false,
            //				'display' => 'search',
            //				'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            //				'choices' => [
            //					$this->translator->trans("En haut à droite", [], 'admin') => "vertical-top-right",
            //					$this->translator->trans("Centré à droite", [], 'admin') => "vertical-center-right",
            //					$this->translator->trans("En en bas à droite", [], 'admin') => "vertical-bottom-right",
            //					$this->translator->trans("En en haut à gauche", [], 'admin') => "vertical-top-left",
            //					$this->translator->trans("Centré à gauche", [], 'admin') => "vertical-center-left",
            //					$this->translator->trans("En en bas à gauche", [], 'admin') => "vertical-bottom-left"
            //				],
            //				'label' => $this->translator->trans("Position du titre", [], 'admin'),
            //				'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4']
            //			]);

            $builder->add('zIndex', WidgetType\ZIndexType::class);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title'],
                'label_fields' => ['title' => $this->translator->trans('Intitulé de la zone', [], 'admin')],
                'placeholder_fields' => ['title' => $this->translator->trans('Saisissez un intitulé', [], 'admin')],
                'fields_data' => ['titleForce' => 2],
            ]);
        }

        $builder->add('shadow', WidgetType\ShadowType::class);
        $builder->add('shadowMobile', WidgetType\ShadowType::class, [
            'label' => $this->translator->trans('Ombre mobile', [], 'admin'),
        ]);

        if ($multiLocales) {
            $builder->add('hideLocales', WebsiteLocalesType::class, [

                'row_attr' => ['class' => $this->isInternalUser ? 'col-md-6' : 'col-12'],
            ]);
        }

        $builder->add('cols', Type\CollectionType::class, [
            'entry_type' => ZoneColsType::class,
            'entry_options' => [
                'zone' => $builder->getData(),
                'website' => $website,
                'attr' => ['class' => 'col-12 col-order'],
            ],
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => [
                'class' => 'btn-app edit-element-submit-btn',
                'data-icon' => 'save',
                'data-icon-side' => 'left',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Zone::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
