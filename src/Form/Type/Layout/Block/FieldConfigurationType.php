<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Core\Entity;
use App\Entity\Layout\Block;
use App\Entity\Layout\FieldConfiguration;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Form\StepForm;
use App\Form\EventListener\Layout\ValuesListener;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Translation\i18nRuntime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FieldConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private bool $isInternalUser;
    private array $options = [];
    private ?FieldConfiguration $data;

    /**
     * FieldConfigurationType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly i18nRuntime $i18nRuntime,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    /**
     * @throws NonUniqueResultException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Form|StepForm $currentForm */
        $currentForm = $options['currentForm'];
        $formConfiguration = $currentForm ? $currentForm->getConfiguration() : false;
        $asDynamic = $formConfiguration && $formConfiguration->isDynamic();
        $fieldType = $options['field_type'];

        $this->options = $options;
        $this->data = $this->options['block'] instanceof Block ? $this->options['block']->getFieldConfiguration() : null;

        if ($this->isInternalUser) {
            $builder->add('slug', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Code', [], 'admin'),
                'attr' => [
                    'group' => 'col-md-2',
                    'placeholder' => $this->translator->trans('Saisissez un code', [], 'admin'),
                ],
            ]);
        }

        $fields = $this->getFields($fieldType);
        foreach ($fields as $field => $value) {
            $getter = is_bool($value) || str_contains($value, 'col') ? 'get'.ucfirst($field) : 'get'.ucfirst($value);
            $groupClass = $value && is_string($value) && str_contains($value, 'col') ? $value : null;
            if (method_exists($this, $getter) && !is_bool($value)
                || method_exists($this, $getter) && is_bool($value) && $this->isInternalUser) {
                $this->$getter($builder, $fieldType, $asDynamic, $groupClass);
            }
        }

        if ($this->isRequired($fieldType, $fields)) {
            $this->getRequired($builder);
        }

        $dynamicFields = ['form-checkbox'];
        if ($asDynamic && in_array($fieldType, $dynamicFields)) {
            $field = new FieldLayoutChoiceType($this->coreLocator, $this->i18nRuntime);
            $field->add($builder, ['layout' => $options['layout']]);
        }

        $builder->add('anonymize', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Anonymiser (RGPD)', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        //        $this->getSmallSize($builder, $fields);
    }

    /**
     * Get required.
     */
    private function getRequired(FormBuilderInterface $builder): void
    {
        $builder->add('required', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Champs obligatoire', [], 'admin'),
            'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
        ]);
    }

    /**
     * Get small size.
     */
    private function getSmallSize(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $label = $this->getLabel($fieldType, 'min');
        $label = !empty($label) ? $label : $this->translator->trans('Reduire la taille de la police', [], 'admin');
        $builder->add('smallSize', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $label,
            'attr' => ['group' => $groupClass ?: 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
        ]);
    }

    /**
     * Get Min IntegerType.
     */
    private function getMin(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $label = $this->getLabel($fieldType, 'min');
        $label = !empty($label) ? $label : $this->translator->trans('Nombre minimun de caractères', [], 'admin');
        $placeholder = $this->getPlaceholder($fieldType, 'min');
        $placeholder = !empty($placeholder) ? $placeholder : $this->translator->trans('Saisissez un chiffre', [], 'admin');

        $builder->add('min', Type\IntegerType::class, [
            'required' => false,
            'label' => $label,
            'attr' => [
                'placeholder' => $placeholder,
                'group' => $groupClass ?: 'col-md-4',
                'min' => 0,
            ],
        ]);
    }

    /**
     * Get Max IntegerType.
     */
    private function getMax(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $label = $this->getLabel($fieldType, 'max');
        $label = !empty($label) ? $label : $this->translator->trans('Nombre maximum de caractères', [], 'admin');
        $placeholder = $this->getPlaceholder($fieldType, 'max');
        $placeholder = !empty($placeholder) ? $placeholder : $this->translator->trans('Saisissez un chiffre', [], 'admin');

        $builder->add('max', Type\IntegerType::class, [
            'required' => false,
            'label' => $label,
            'attr' => [
                'placeholder' => $placeholder,
                'group' => $groupClass ?: 'col-md-4',
                'min' => 0,
            ],
        ]);
    }

    /**
     * Get Max IntegerType.
     */
    private function getDateDisplay(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $label = $this->getLabel($fieldType, 'buttonType');
        $label = !empty($label) ? $label : $this->translator->trans('Affichage des dates', [], 'admin');
        $placeholder = $this->getPlaceholder($fieldType, 'buttonType');
        $placeholder = !empty($placeholder) ? $placeholder : $this->translator->trans('Sélectionnez', [], 'admin');

        $builder->add('buttonType', Type\ChoiceType::class, [
            'required' => false,
            'label' => $label,
            'display' => 'search',
            'placeholder' => $placeholder,
            'choices' => [
                $this->translator->trans('Avant la date en cours (comprise)', [], 'admin') => 'before-current-in',
                $this->translator->trans('Après la date en cours (comprise)', [], 'admin') => 'after-current-in',
                $this->translator->trans('Avant la date en cours (non comprise)', [], 'admin') => 'before-current-out',
                $this->translator->trans('Après la date en cours (non comprise)', [], 'admin') => 'after-current-out',
            ],
            'attr' => [
                'group' => $groupClass ?: 'col-md-4',
                'min' => 0,
            ],
        ]);
    }

    /**
     * Get Max IntegerType.
     */
    private function getRegex(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $builder->add('regex', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('Expression regulière', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez une expression', [], 'admin'),
                'group' => $groupClass ?: 'col-md-4',
            ],
            'help' => $this->translator->trans('Ex: /^[0-9]*$/', [], 'admin'),
        ]);
    }

    /**
     * Get Multiple CheckboxType.
     */
    private function getMultiple(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $builder->add('multiple', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Choix multiple', [], 'admin'),
            'attr' => ['group' => $groupClass ?: 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
        ]);
    }

    /**
     * Get display Checkbox.
     */
    private function getExpanded(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $builder->add('expanded', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher les cases à cocher', [], 'admin'),
            'attr' => ['group' => $groupClass ?: 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
        ]);
    }

    /**
     * Get display Picker.
     */
    private function getPicker(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $label = $this->getLabel($fieldType, 'picker');
        $label = !empty($label) ? $label : $this->translator->trans('Afficher un picker', [], 'admin');
        $builder->add('picker', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $label,
            'attr' => ['group' => $groupClass ? $groupClass.' d-flex align-items-end' : 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
        ]);
    }

    /**
     * Get display inline.
     */
    private function getInline(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $label = $this->getLabel($fieldType, 'inline');
        $label = !empty($label) ? $label : $this->translator->trans('Afficher en ligne', [], 'admin');
        $builder->add('inline', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $label,
            'attr' => ['group' => $groupClass ?: 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
        ]);
    }

    /**
     * Get File Types.
     */
    private function getFilesTypes(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $builder->add('filesTypes', Type\ChoiceType::class, [
            'required' => false,
            'multiple' => true,
            'display' => 'search',
            'label' => $this->translator->trans('Types de fichiers', [], 'admin'),
            'attr' => ['group' => $groupClass ?: 'col-12'],
            'choices' => $this->getMimeTypes(),
        ]);
    }

    /**
     * Get max file size.
     */
    private function getMaxFileSize(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $builder->add('maxFileSize', Type\IntegerType::class, [
            'required' => false,
            'label' => $this->translator->trans('Poid maximum en kilobyte', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                'group' => $groupClass ?: 'col-md-4',
                'min' => 1,
            ],
        ]);
    }

    /**
     * Get script.
     */
    private function getScript(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $builder->add('script', Type\TextareaType::class, [
            'required' => false,
            'editor' => false,
            'label' => $this->translator->trans('Script', [], 'admin'),
            'attr' => [
                'group' => $groupClass ?: 'col-12',
                'placeholder' => $this->translator->trans('Ajouter un script', [], 'admin'),
            ],
        ]);
    }

    /**
     * Get entity selector.
     */
    private function getEntity(FormBuilderInterface $builder, string $fieldType, bool $asDynamic, ?string $groupClass = null): void
    {
        $choices = [];
        $entities = $this->entityManager->getRepository(Entity::class)->findBy([
            'inFieldConfiguration' => true,
            'website' => $this->options['website'],
        ]);
        foreach ($entities as $entity) {
            $choices[$entity->getClassName()] = $entity->getClassName();
        }

        if (!$entities && $this->isInternalUser) {
            $session = new Session();
            $session->getFlashBag()->add('error', $this->translator->trans('Rendez-vous dans la configuration des entités.'));
        }

        $builder->add('className', Type\ChoiceType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->translator->trans('Entité', [], 'admin'),
            'choices' => $choices,
            'attr' => [
                'group' => $groupClass ?: 'col-md-3',
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        if ($this->data instanceof FieldConfiguration && $this->data->getClassName()) {
            $referEntity = new ($this->data->getClassName())();
            if (method_exists($referEntity, 'getCatalog')) {
                $catalogs = $this->entityManager->getRepository(Catalog::class)->findBy(['website' => $this->options['website']]);
                $choices = [];
                foreach ($catalogs as $catalog) {
                    $choices[$catalog->getAdminName()] = 'catalog-'.$catalog->getSlug();
                }
                $builder->add('masterField', Type\ChoiceType::class, [
                    'required' => false,
                    'display' => 'search',
                    'label' => $this->translator->trans('Catalogue', [], 'admin'),
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'choices' => $choices,
                    'attr' => [
                        'group' => $groupClass ?: 'col-md-3',
                    ],
                ]);
            }
        }
    }

    /**
     * Get display Checkbox.
     */
    private function getValues(FormBuilderInterface $builder, string $fieldType, bool $asDynamic): void
    {
        $options['website'] = $this->options['website'];
        $options['field_type'] = $this->options['field_type'];
        $options['currentForm'] = $this->options['currentForm'];
        $options['layout'] = $this->options['layout'];
        $options['asDynamic'] = $asDynamic;

        $builder->add('fieldValues', CollectionType::class, [
            'label' => false,
            'entry_type' => FieldValueType::class,
            'prototype' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'entry_options' => $options,
        ])->addEventSubscriber(new ValuesListener($this->coreLocator, $this->options));
    }

    /**
     * Get fields for field type.
     */
    private function getFields(string $fieldType): array
    {
        $fields['form-text'] = ['min', 'max', 'regex' => true];
        $fields['form-textarea'] = ['min', 'max', 'regex' => true];
        $fields['form-password'] = ['min', 'max', 'regex' => true];
        $fields['form-integer'] = ['min' => 'col-md-6', 'max' => 'col-md-6'];
        $fields['form-gdpr'] = ['values', 'smallSize'];
        $fields['form-choice-type'] = ['multiple', 'expanded', 'values', 'picker', 'inline'];
        $fields['form-date'] = ['picker' => 'col-md-3', 'min' => 'col-md-4', 'max' => 'col-md-4', 'required' => 'col-md-3', 'dateDisplay' => 'col-md-4'];
        $fields['form-datetime'] = ['picker' => 'col-md-3', 'min' => 'col-md-2', 'max' => 'col-md-2', 'required' => 'col-md-3'];
        $fields['form-hour'] = ['picker'];
        $fields['form-file'] = ['filesTypes' => 'col-md-8', 'maxFileSize' => 'col-md-4', 'multiple'];
        $fields['form-emails'] = ['multiple', 'expanded', 'values', 'picker'];
        $fields['form-country'] = ['picker'];
        $fields['form-language'] = ['picker'];
        $fields['form-choice-entity'] = ['entity', 'multiple', 'expanded' , 'picker'];
        $fields['form-submit'] = ['script'];

        return $fields[$fieldType] ?? [];
    }

    /**
     * Get fields label.
     */
    private function getLabel(string $fieldType, string $field): ?string
    {
        $fields['form-choice-type'] = ['picker' => $this->translator->trans('Afficher le moteur de recherche', [], 'admin')];
        $fields['form-emails'] = ['picker' => $this->translator->trans('Afficher le moteur de recherche', [], 'admin')];
        $fields['form-country'] = ['picker' => $this->translator->trans('Afficher le moteur de recherche', [], 'admin')];
        $fields['form-language'] = ['picker' => $this->translator->trans('Afficher le moteur de recherche', [], 'admin')];
        $fields['form-integer'] = [
            'min' => $this->translator->trans('Minimum', [], 'admin'),
            'max' => $this->translator->trans('Maximum', [], 'admin'),
            'picker' => $this->translator->trans('Afficher les selecteurs', [], 'admin'),
        ];
        $fields['form-date'] = [
            'min' => $this->translator->trans('Année de début', [], 'admin'),
            'max' => $this->translator->trans('Année de fin', [], 'admin'),
            'buttonType' => $this->translator->trans('Affichage des dates', [], 'admin'),
        ];

        return $fields[$fieldType][$field] ?? null;
    }

    /**
     * Get fields placeholder.
     */
    private function getPlaceholder(string $fieldType, string $field): ?string
    {
        $fields['form-date'] = $fields['form-datetime'] = [
            'min' => $this->translator->trans('Saisissez une année', [], 'admin'),
            'max' => $this->translator->trans('Saisissez une année', [], 'admin'),
            'buttonType' => $this->translator->trans('Sélectionnez', [], 'admin'),
        ];

        return $fields[$fieldType][$field] ?? null;
    }

    /**
     * Get fields for field type.
     */
    private function isRequired(string $fieldType, array $fieldsConfig = []): bool
    {
        foreach ($fieldsConfig as $key => $value) {
            if ('required' === $key || 'required' === $value) {
                return false;
            }
        }
        $fields['form-submit'] = false;
        $fields['form-hidden'] = false;

        return $fields[$fieldType] ?? true;
    }

    /**
     * Get mimeTypes.
     */
    private function getMimeTypes(): array
    {
        $mimeTypes = ['.xlsx', '.xls', 'image/*', '.doc', '.docx', '.txt', '.pdf', '.mp4', '.mp3'];
        $choices = [];
        foreach ($mimeTypes as $mimeType) {
            $choices[$mimeType] = $mimeType;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FieldConfiguration::class,
            'website' => null,
            'layout' => null,
            'block' => null,
            'currentForm' => null,
            'field_type' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
