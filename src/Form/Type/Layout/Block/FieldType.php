<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Repository\Core\IconRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FieldType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * FieldType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly IconRepository $iconRepository,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Block $data */
        $block = $builder->getData();
        $blockTypeSlug = $block->getBlockType()->getSlug();
        $currentForm = $options['currentForm'];
        $layout = $options['layout'];
        $isSubmit = 'form-submit' === $blockTypeSlug;
        $isFile = 'form-file' === $blockTypeSlug;
        $isEntity = 'form-choice-entity' === $blockTypeSlug;
        $asBtnField = $isSubmit || $isFile;

        $adminNameClass = $this->isInternalUser ? 'col-md-10' : 'col-12';

        if ($asBtnField && $this->isInternalUser) {
            $adminNameClass = 'col-md-6';
        } elseif ($isEntity) {
            $adminNameClass = 'col-md-7';
        } elseif ($asBtnField) {
            $adminNameClass = 'col-md-10';
        }

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => $adminNameClass]);

        if ($asBtnField) {
            $builder->add('color', WidgetType\ButtonColorType::class, [
                'label' => 'form-file' === $blockTypeSlug ? $this->translator->trans('Couleur de fond "Parcourir"', [], 'admin') : $this->translator->trans('Style de bouton', [], 'admin'),
                'attr' => ['class' => 'select-icons', 'group' => 'col-md-2'],
            ]);

            if ($this->isInternalUser) {
                $builder->add('icon', WidgetType\IconType::class, [
                    'attr' => ['class' => 'select-icons', 'group' => 'col-md-2'],
                    'choices' => $this->getIcons($options['website']),
                ]);
            }
        }

        if ('form-file' === $blockTypeSlug) {
            $builder->add('controls', CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher un bouton', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);
        }

        $errorLabel = $this->translator->trans("Message d'erreur", [], 'admin');
        $labels = $this->getLabels($block);
        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => $this->getFields($block),
            'label_fields' => [
                'title' => $labels->label,
                'targetLabel' => $labels->targetLabel,
                'error' => $errorLabel,
            ],
            'placeholder_fields' => ['title' => $labels->placeholder],
            'target_config' => false,
        ]);

        $builder->add('fieldConfiguration', FieldConfigurationType::class, [
            'label' => false,
            'field_type' => $block->getBlockType()->getSlug(),
            'website' => $options['website'],
            'currentForm' => $currentForm,
            'layout' => $layout,
            'block' => $block,
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true, 'btn_both_label' => $this->translator->trans('Enregistrer et retourner à la mise en page', [], 'admin')]);
    }

    /**
     * Get fields by BlockType.
     */
    private function getFields(Block $block): array
    {
        $type = $block->getBlockType()->getSlug();
        $fields['base'] = ['title' => 'col-md-4', 'placeholder' => 'col-md-4', 'help' => 'col-md-4'];
        $fields['form-checkbox'] = ['title' => 'col-md-12', 'help' => 'col-md-6', 'error' => 'col-md-6'];
        $fields['form-choice-entity'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-choice-type'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-text'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-email'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-phone'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-integer'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-zip-code'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-textarea'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-country'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-language'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-emails'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-date'] = ['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-hour'] =['title' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3', 'error' => 'col-md-3'];
        $fields['form-file'] = ['title' => 'col-md-3', 'targetLabel' => 'col-md-3', 'placeholder' => 'col-md-3', 'help' => 'col-md-3'];
        $fields['form-submit'] = ['title' => 'col-md-6', 'help'];
        $fields['form-hidden'] = ['title'];

        return $fields[$type] ?? $fields['base'];
    }

    /**
     * Get labels by BlockType.
     */
    private function getLabels(Block $block): object
    {
        $type = $block->getBlockType()->getSlug();
        $labels['base'] = [
            'label' => $this->translator->trans('Label', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un label', [], 'admin'),
            'targetLabel' => $this->translator->trans('Label du bouton', [], 'admin'),
        ];
        $labels['form-hidden'] = [
            'label' => $this->translator->trans('Valeur', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez une valeur', [], 'admin'),
        ];
        $response = $labels[$type] ?? $labels['base'];
        if (empty($response['label'])) {
            $response['label'] = $labels['base']['label'];
        }
        if (empty($response['placeholder'])) {
            $response['placeholder'] = $labels['base']['placeholder'];
        }
        if (empty($response['targetLabel'])) {
            $response['targetLabel'] = $labels['base']['targetLabel'];
        }

        return (object) $response;
    }

    /**
     * Get WebsiteModel icons.
     */
    private function getIcons(Website $website): array
    {
        $icons = $this->iconRepository->findBy(['configuration' => $website->getConfiguration()]);
        $choices = [];
        $choices[$this->translator->trans('Séléctionnez', [], 'admin')] = '';
        foreach ($icons as $icon) {
            $choices[$icon->getPath()] = $icon->getPath();
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'translation_domain' => 'admin',
            'layout' => null,
            'currentForm' => null,
            'website' => null,
        ]);
    }
}
