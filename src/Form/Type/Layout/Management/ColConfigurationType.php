<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Core\Configuration;
use App\Entity\Layout\Col;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ColConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * ZoneConfigurationType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $website = $options['website'];

        /** @var Configuration $configuration */
        $configuration = $options['website']->getConfiguration();
        $multiLocales = count($configuration->getAllLocales()) > 1;

        $margins = new MarginType($this->coreLocator);
        $margins->add($builder);

        $builder->add('fullSize', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Toute la largeur', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $builder->add('customClass', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Classes personnalisées', [], 'admin'),
            'attr' => [
                'group' => 'col-md-8',
                'class' => 'input-css',
                'placeholder' => $this->translator->trans('Éditer', [], 'admin'),
            ],
        ]);

        $builder->add('customId', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Id personnalisé', [], 'admin'),
            'attr' => [
                'group' => 'col-md-4',
                'placeholder' => $this->translator->trans('Éditer', [], 'admin'),
            ],
        ]);

        if ($this->isInternalUser) {
            $builder->add('zIndex', WidgetType\ZIndexType::class);
        }

        $builder->add('shadow', WidgetType\ShadowType::class);
        $builder->add('shadowMobile', WidgetType\ShadowType::class, [
            'label' => $this->translator->trans('Ombre mobile', [], 'admin'),
        ]);

        $builder->add('verticalAlign', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Centrer verticalement le contenu colonne', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $builder->add('endAlign', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Aligner en bas de la colonne', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $builder->add('reverse', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher la colonne en première position sur mobile', [], 'admin'),
            'attr' => ['group' => 'col-md-4', 'class' => 'w-100'],
        ]);

        $builder->add('sticky', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Colonne fixe', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $builder->add('hideMobile', HideType::class, [
            'label' => $this->translator->trans('Cacher la colonne sur mobile', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('hideTablet', HideType::class, [
            'label' => $this->translator->trans('Cacher la colonne sur tablette', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('hideMiniPc', HideType::class, [
            'label' => $this->translator->trans('Cacher la colonne sur mini PC', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('hideDesktop', HideType::class, [
            'label' => $this->translator->trans('Cacher la colonne sur PC', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('hide', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Cacher la colonne', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $ordersSizes = new ScreensType($this->coreLocator);
        $ordersSizes->add($builder, [
            'parentCount' => true,
            'entity' => $builder->getData(),
            'mobilePositionLabel' => true,
            'mobilePositionGroup' => 'col-md-4',
            'tabletPositionLabel' => true,
            'tabletPositionGroup' => 'col-md-4',
            'miniPcPositionLabel' => true,
            'miniPcPositionGroup' => 'col-md-4',
            'mobileSizeLabel' => true,
            'mobileSizeGroup' => 'col-md-4',
            'tabletSizeLabel' => true,
            'tabletSizeGroup' => 'col-md-4',
            'miniPCSizeLabel' => true,
            'miniPCSizeGroup' => 'col-md-4',
        ]);

        $builder->add('alignment', AlignmentType::class, [
            'attr' => ['group' => 'col-md-6'],
        ]);

        $builder->add('elementsAlignment', Type\ChoiceType::class, [
            'required' => false,
            'label' => $this->translator->trans('Alignement des blocs', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'display' => 'search',
            'choices' => [
                $this->translator->trans('À gauche', [], 'admin') => 'd-flex justify-content-start',
                $this->translator->trans('Centré', [], 'admin') => 'd-flex justify-content-center',
                $this->translator->trans('À droite', [], 'admin') => 'd-flex justify-content-end',
            ],
            'attr' => ['group' => 'col-md-6'],
        ]);

        if ($multiLocales) {
            $builder->add('hideLocales', WidgetType\WebsiteLocalesType::class);
        }

        $transitions = new TransitionType($this->coreLocator);
        $transitions->add($builder, ['website' => $website]);

        $radiusType = new WidgetType\RadiusType($this->coreLocator);
        $radiusType->add($builder);

        $builder->add('blocks', Type\CollectionType::class, [
            'entry_type' => ColBlocksType::class,
            'entry_options' => [
                'col' => $builder->getData(),
                'website' => $website,
                'attr' => ['class' => 'col-order'],
            ],
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => ['class' => 'btn-info edit-element-submit-btn'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Col::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
