<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType as SymfonySubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SubmitType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SubmitType
{
    private TranslatorInterface $translator;
    private ?string $ajaxClass = null;
    private ?string $refreshClass = null;

    /**
     * SubmitType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * To add submit buttons.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $data = $builder->getData();
        $isNew = !$data || method_exists($data, 'getId') && !$data->getId() || !method_exists($data, 'getId');

        $this->ajaxClass = !empty($options['as_ajax']) ? ' ajax-post' : '';
        $this->refreshClass = !empty($options['refresh']) ? ' refresh' : '';

        if (!empty($options['only_save'])) {
            $this->saveButton($builder, $isNew, $options);
        } elseif (!empty($options['btn_back'])) {
            $this->saveBack($builder, $options);
        } elseif ($data && $isNew) {
            $this->newButtons($builder, $options);
        } elseif ($data) {
            $this->saveButton($builder, $isNew, $options);
        }

        if (!empty($options['btn_both']) && !$isNew) {
            $this->saveBack($builder, $options);
        }

        if (!empty($options['btn_add'])) {
            $this->saveAdd($builder, $options);
        }
    }

    /**
     * New buttons.
     */
    private function newButtons(FormBuilderInterface $builder, array $options = []): void
    {
        $builder->add('save', SymfonySubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => [
                'class' => 'btn-info standard'.$this->ajaxClass.$this->refreshClass,
                'data-icon' => 'fal save',
                'data-icon-side' => 'left',
            ],
        ]);
        $builder->add('saveEdit', SymfonySubmitType::class, [
            'label' => $this->translator->trans('Enregistrer et éditer', [], 'admin'),
            'attr' => [
                'class' => 'btn-info standard'.$this->ajaxClass.$this->refreshClass,
                'data-icon' => 'fal save',
                'data-icon-side' => 'left',
            ],
        ]);
    }

    /**
     * Save buttons.
     */
    private function saveButton(FormBuilderInterface $builder, bool $isNew, array $options = []): void
    {
        $label = !empty($options['btn_save_label']) ? $options['btn_save_label'] : $this->translator->trans('Enregistrer', [], 'admin');
        $builder->add('save', SymfonySubmitType::class, [
            'label' => $label,
            'attr' => [
                'class' => isset($options['class']) ? $options['class'].$this->ajaxClass.$this->refreshClass : 'btn-info standard'.$this->ajaxClass.$this->refreshClass,
                'force' => $options['force'] ?? false,
                'data-icon' => 'fal save',
                'data-icon-side' => 'left',
            ],
        ]);
    }

    /**
     * Save and go back buttons.
     */
    private function saveBack(FormBuilderInterface $builder, array $options = []): void
    {
        if (!empty($options['btn_both_label'])) {
            $label = $options['btn_both_label'];
        } else {
            $label = !empty($options['btn_both'])
                ? $this->translator->trans('Enregistrer et retourner à la liste', [], 'admin')
                : $this->translator->trans('Enregistrer', [], 'admin');
        }
        $builder->add('saveBack', SymfonySubmitType::class, [
            'label' => $label,
            'attr' => [
                'class' => 'btn-info standard',
                'data-icon' => 'fal save',
                'data-icon-side' => 'left',
            ],
        ]);
    }

    /**
     * Save, go to index and open creation modal.
     */
    private function saveAdd(FormBuilderInterface $builder, array $options = []): void
    {
        $builder->add('saveAdd', SymfonySubmitType::class, [
            'label' => $this->translator->trans('Enregistrer et ajouter', [], 'admin'),
            'attr' => [
                'class' => 'btn-info standard',
                'data-icon' => 'fal save',
                'data-icon-side' => 'left',
            ],
        ]);
    }
}
