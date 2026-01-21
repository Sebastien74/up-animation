<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Core\Configuration;
use App\Entity\Layout\Block;
use App\Entity\Layout\BlockType;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BlockConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockConfigurationType extends AbstractType
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
        /** @var Configuration $configuration */
        $configuration = $options['website']->getConfiguration();
        $multiLocales = count($configuration->getAllLocales()) > 1;

        /** @var Block $block */
        $block = $builder->getData();
        $blockTypeSlug = $block instanceof Block && $block->getBlockType() instanceof BlockType ? $block->getBlockType()->getSlug() : null;

        $margins = new MarginType($this->coreLocator);
        $margins->add($builder);

        $screens = [
            '' => $this->translator->trans('Ordinateur', [], 'admin'),
            'MiniPc' => $this->translator->trans('Mini PC', [], 'admin'),
            'Tablet' => $this->translator->trans('Tablette', [], 'admin'),
            'Mobile' => $this->translator->trans('Mobile', [], 'admin'),
        ];
        foreach ($screens as $screen => $label) {
            $builder->add('alignment'.$screen, Type\ChoiceType::class, [
                'label' => $this->translator->trans('Alignement du contenu <small>('.$label.')</small>', [], 'admin'),
                'label_attr' => ['class' => 'text-start'],
                'required' => false,
                'display' => 'search',
                'placeholder' => $this->translator->trans('Par défaut', [], 'admin'),
                'choices' => [
                    $this->translator->trans('Gauche', [], 'admin') => 'start',
                    $this->translator->trans('Centré', [], 'admin') => 'center',
                    $this->translator->trans('Droite', [], 'admin') => 'end',
                    $this->translator->trans('Justifié', [], 'admin') => 'justify',
                ],
            ]);
        }

        $builder->add('elementsAlignment', Type\ChoiceType::class, [
            'required' => false,
            'label' => $this->translator->trans('Alignement du bloc', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => $this->isInternalUser ? 'col-md-6' : 'col-12'],
            'display' => 'search',
            'choices' => [
                $this->translator->trans('À gauche', [], 'admin') => 'd-flex justify-content-start',
                $this->translator->trans('Centré', [], 'admin') => 'd-flex justify-content-center',
                $this->translator->trans('À droite', [], 'admin') => 'd-flex justify-content-end',
            ],
        ]);

        if ($this->isInternalUser) {
            $builder->add('zIndex', WidgetType\ZIndexType::class, ['attr' => ['group' => 'col-md-6']]);
        }

        if ('media' === $blockTypeSlug || 'text' === $blockTypeSlug || 'title' === $blockTypeSlug) {
            $builder->add('useForThumb', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Utiliser pour les vignettes', [], 'admin'),
                'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
            ]);
        }

        $builder->add('verticalAlign', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Centrer verticalement le contenu du bloc', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $builder->add('endAlign', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Aligner le contenu en bas du bloc', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $builder->add('hide', HideType::class, [
            'label' => $this->translator->trans('Cacher le bloc', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $builder->add('hideDesktop', HideType::class, [
            'label' => $this->translator->trans('Cacher le bloc sur PC', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('hideMiniPc', HideType::class, [
            'label' => $this->translator->trans('Cacher le bloc sur mini PC', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('hideTablet', HideType::class, [
            'label' => $this->translator->trans('Cacher le bloc sur tablette', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('hideMobile', HideType::class, [
            'label' => $this->translator->trans('Cacher le bloc sur mobile', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $radiusType = new WidgetType\RadiusType($this->coreLocator);
        $radiusType->add($builder);

        $builder->add('reverse', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher le bloc en première position sur mobile', [], 'admin'),
            'attr' => ['group' => 'col-md-6', 'class' => 'w-100'],
        ]);

        $ordersSizes = new ScreensType($this->coreLocator);
        $ordersSizes->add($builder, [
            'entity' => $builder->getData()->getCol(),
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

        if ($multiLocales) {
            $builder->add('hideLocales', WidgetType\WebsiteLocalesType::class);
        }

        $transitions = new TransitionType($this->coreLocator);
        $transitions->add($builder, ['website' => $options['website']]);

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

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => ['class' => 'btn-info edit-element-submit-btn'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
