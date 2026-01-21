<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Core\Icon;
use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LinkType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LinkType extends AbstractType
{
    private const bool CTA_TEXT = false;
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private array $icons;
    private bool $isInternalUser;

    /**
     * LinkType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $website = $this->coreLocator->website()->entity;
        $this->icons = $this->entityManager->getRepository(Icon::class)->findBy(['configuration' => $website->getConfiguration()]);
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        if (!empty($this->icons) && self::CTA_TEXT) {

            $builder->add('icon', WidgetType\IconType::class, [
                'required' => false,
                'attr' => ['class' => 'select-icons', 'group' => 'col-md-3', 'data-config' => true],
            ]);

            $builder->add('iconSize', ChoiceType::class, [
                'required' => false,
                'display' => 'search',
                'label' => $this->translator->trans("Taille de l'icône", [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'data-config' => true],
                'choices' => ['XS' => 'xs', 'S' => 'sm', 'M' => 'md', 'L' => 'lg', 'XL' => 'xl', 'XXL' => 'xxl'],
            ]);

            $builder->add('iconPosition', ChoiceType::class, [
                'required' => false,
                'display' => 'search',
                'label' => $this->translator->trans("Position de l'icône", [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'data-config' => true],
                'choices' => [
                    $this->translator->trans('En haut', [], 'admin') => 'top',
                    $this->translator->trans('À droite', [], 'admin') => 'right',
                    $this->translator->trans('En bas', [], 'admin') => 'bottom',
                    $this->translator->trans('À gauche', [], 'admin') => 'left',
                ],
            ]);

            $builder->add('color', WidgetType\AppColorType::class, [
                'label' => $this->translator->trans("Couleur de l'icône", [], 'admin'),
                'attr' => ['class' => 'select-icons', 'group' => 'col-md-3', 'data-config' => true],
            ]);

            $builder->add('backgroundColorType', WidgetType\ButtonColorType::class, [
                'label' => $this->translator->trans('Style du lien du CTA', [], 'admin'),
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-3',
                ],
            ]);
        }

        $fields = self::CTA_TEXT ? ['targetLink' => 'col-md-8', 'targetPage' => 'col-md-4', 'placeholder' => 'col-md-4', 'targetLabel' => 'col-md-4', 'targetStyle' => 'col-md-4', 'introduction']
            : ['targetLink' => 'col-md-12', 'targetPage' => 'col-md-4', 'targetLabel' => 'col-md-4', 'targetStyle' => 'col-md-4'];
        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => $fields,
            'label_fields' => ['placeholder' => $this->translator->trans('Texte associé au lien de type CTA', [], 'admin')],
            'placeholder_fields' => ['placeholder' => $this->translator->trans('Saisissez votre texte', [], 'admin')],
            'groups_fields' => ['newTab' => 'col-md-4'],
        ]);

        if ($this->isInternalUser) {
            $builder->add('script', Type\TextareaType::class, [
                'required' => false,
                'editor' => false,
                'label' => $this->translator->trans('Script (SEO Tracking)', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Ajouter le script', [], 'admin'),
                ],
            ]);
        }

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
