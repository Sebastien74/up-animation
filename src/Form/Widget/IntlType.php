<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Media\Folder;
use App\Repository\Core\WebsiteRepository;
use App\Repository\Media\FolderRepository;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * IntlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IntlType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?Request $request;
    private array $options = [];
    private ?Website $website;
    private array $websites = [];

    /**
     * intlType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository $websiteRepository,
        private readonly FolderRepository $folderRepository,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->request = $this->coreLocator->requestStack()->getMainRequest();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->options = $options;
        $this->website = $options['website'];
        $this->websites = $this->websiteRepository->findAll();
        if (!$this->website && $this->request->get('website')) {
            $this->website = $this->websiteRepository->find($this->request->get('website'));
        }

        $haveLink = false;
        foreach ($options['fields'] as $key => $name) {
            $field = is_int($key) ? $name : $key;
            if (str_contains($field, 'target')) {
                $haveLink = true;
            }
        }

        foreach ($options['fields'] as $key => $name) {
            $field = is_int($key) ? $name : $key;
            $groupClass = is_int($key) ? 'col-12' : $name;
            $getter = 'get'.ucfirst($field);
            $isValid = !$this->options['target_config'] || $this->options['target_config'] && !$haveLink && 'newTab' === $field || 'newTab' !== $field;
            if ($isValid && method_exists($this, $getter)) {
                $this->$getter($builder, $field, $groupClass);
            } elseif (!empty($options['extra_fields'][$field])) {
                $this->getExtraField($builder, $field, $options);
            }
        }

        foreach ($options['extra_fields'] as $field => $config) {
            $this->getExtraField($builder, $field, $options);
        }

        if ($haveLink && $this->options['target_config']) {
            $this->getTargetFields($builder);
        }
    }

    /**
     * Extra field.
     */
    private function getExtraField(FormBuilderInterface $builder, string $field, ?array $options = []): void
    {
        $type = !empty($options['extra_fields'][$field]['type']) ? $options['extra_fields'][$field]['type'] : Type\TextType::class;
        if (!empty($options['extra_fields'][$field]['type'])) {
            unset($options['extra_fields'][$field]['type']);
        }
        $builder->add($field, $type, $options['extra_fields'][$field]);
        unset($options['extra_fields'][$field]);
    }

    /**
     * Title field.
     */
    private function getTitle(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextType::class;

        if ($this->options['title_force']) {
            $builder->add('titleForce', Type\ChoiceType::class, [
                'required' => false,
                'display' => 'search',
                'label' => $this->getAttribute('titleForce', 'label'),
                'placeholder' => $this->getAttribute('titleForce', 'placeholder'),
                'attr' => [
                    'group' => 'col-md-2',
                    'data-placeholder' => $this->getAttribute('titleForce', 'placeholder'),
                ],
                'choices' => ['H1' => 1, 'H2' => 2, 'H3' => 3, 'H4' => 4, 'H5' => 5, 'H6' => 6],
                'help' => $this->getAttribute('titleForce', 'help'),
            ]);
        }

        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, $fieldType, [
            'required' => $constraints['required'],
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => 'col-12' === $groupClass && $this->options['title_force'] ? 'col-md-10' : $groupClass,
            ]),
            'constraints' => $constraints['validators'],
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * SubTitle field.
     */
    private function getSubTitle(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextType::class;
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];

        $builder->add($field, $fieldType, [
            'required' => $constraints['required'],
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-5',
            ]),
            'constraints' => $constraints['validators'],
            'help' => $this->getAttribute($field, 'help'),
        ]);

        if (in_array('subTitlePosition', $this->options['config_fields'])
            || in_array('subTitlePosition', $this->options['fields'])
            || isset($this->options['fields']['subTitlePosition'])) {
            $constraints = $this->getConstraints('subTitlePosition');
            $builder->add('subTitlePosition', Type\ChoiceType::class, [
                'required' => $constraints['required'],
                'label' => $this->getAttribute('subTitlePosition', 'label'),
                'display' => 'search',
                'placeholder' => $this->getAttribute('subTitlePosition', 'placeholder'),
                'choices' => [
                    $this->translator->trans('Haut', [], 'admin') => 'top',
                    $this->translator->trans('Bas', [], 'admin') => 'bottom',
                ],
                'attr' => [
                    'group' => !empty($this->options['fields']['subTitlePosition']) ? $this->options['fields']['subTitlePosition'] : 'col-md-3',
                ],
                'constraints' => $constraints['validators'],
                'help' => $this->getAttribute($field, 'help'),
            ]);
        }
    }

    /**
     * Introduction field.
     */
    private function getIntroduction(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextareaType::class;
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];

        $builder->add($field, $fieldType, [
            'required' => $constraints['required'],
            'editor' => str_contains($groupClass, 'editor'),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => $groupClass,
            ]),
            'constraints' => $constraints['validators'],
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Body field.
     */
    private function getBody(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextareaType::class;
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $attributes = array_merge($attributes, [
            'placeholder' => $this->getAttribute($field, 'placeholder'),
            'group' => $groupClass,
        ]);
        if (!empty($this->options['fields_data'][$field])) {
            $attributes = array_merge($attributes, $this->options['fields_data'][$field]);
        }
        $attributes['data-turbo'] = false;
        $builder->add($field, $fieldType, [
            'required' => $constraints['required'],
            'label' => $this->getAttribute($field, 'label'),
            'editor' => !str_contains($groupClass, 'no-editor'),
            'attr' => $attributes,
            'help' => $this->getAttribute($field, 'help'),
            'constraints' => $constraints['validators'],
        ]);
    }

    /**
     * Target Link field.
     */
    private function getTargetLink(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, Type\TextType::class, [
            'required' => $constraints['required'],
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-12',
            ]),
            'help' => $this->getAttribute($field, 'help'),
            'constraints' => $constraints['validators'],
        ]);
    }

    /**
     * Target Link field.
     */
    private function getTargetPage(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, EntityType::class, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'display' => 'search',
            'class' => Page::class,
            'placeholder' => $this->getAttribute($field, 'placeholder'),
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('p')
                    ->leftJoin('p.urls', 'u')
                    ->leftJoin('p.website', 'w')
                    ->andWhere('p.slug NOT IN (:slugs)')
                    ->andWhere('u.archived = :archived')
                    ->setParameter(':archived', false)
                    ->setParameter(':slugs', ['error', 'components'])
                    ->addSelect('u')
                    ->addSelect('w')
                    ->orderBy('p.adminName', 'ASC');
            },
            'choice_label' => function (Page $page) {
                $label = count($this->websites) > 1
                    ? strip_tags($page->getAdminName()).' ( '.strip_tags($page->getWebsite()->getAdminName()).' )'
                    : strip_tags($page->getAdminName());
                if ($page->isInfill()) {
                    $label .= ' ('.$this->translator->trans('Pour arbo', [], 'admin').')';
                }

                return $label;
            },
            'attr' => array_merge($attributes, [
                //                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-6',
            ]),
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Target Link field.
     */
    private function getTargetLabel(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, Type\TextType::class, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-6',
            ]),
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Target Link field.
     */
    private function getTargetStyle(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, ButtonColorType::class, [
            'attr' => array_merge($attributes, [
                'class' => 'select-icons',
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-6',
            ]),
        ]);
    }

    /**
     * Target Link fields.
     */
    private function getTargetFields(FormBuilderInterface $builder, ?string $groupClass = null): void
    {
        if (!in_array('targetStyle', $this->options['excludes_fields']) && !in_array('targetStyle', $this->options['fields']) && !isset($this->options['fields']['targetStyle'])) {
            $attributes = !empty($this->options['attributes_fields']['targetStyle']) ? $this->options['attributes_fields']['targetStyle'] : [];
            $builder->add('targetStyle', ButtonColorType::class, [
                'attr' => array_merge($attributes, [
                    'class' => 'select-icons',
                    'group' => !empty($this->options['fields']['targetStyle']) ? $this->options['fields']['targetStyle'] : 'col-md-6',
                ]),
            ]);
        }
        $this->getNewTab($builder, 'newTab');
    }

    /**
     * Placeholder field.
     */
    private function getPlaceholder(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $isEditor = str_contains($groupClass, 'editor');
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : ($isEditor ? Type\TextareaType::class : Type\TextType::class);
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, $fieldType, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'editor' => $isEditor ? 'tinymce' : 'basic',
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-6',
            ]),
            'help' => $this->getAttribute($field, 'help'),
            'constraints' => $constraints['validators'],
        ]);
    }

    /**
     * Author field.
     */
    private function getAuthor(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextType::class;
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, $fieldType, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-3',
            ]),
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * AuthorType field.
     */
    private function getAuthorType(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, Type\TextType::class, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-3',
            ]),
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Help field.
     */
    private function getHelp(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextType::class;
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, $fieldType, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-6',
            ]),
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Error field.
     */
    private function getError(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextType::class;
        $attributes = !empty($this->options['attributes_fields'][$field]) ? $this->options['attributes_fields'][$field] : [];
        $builder->add($field, $fieldType, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => array_merge($attributes, [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-6',
            ]),
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * New tab field.
     */
    private function getNewTab(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $groupClass = !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-12';
        $groupClass = !empty($this->options['groups_fields'][$field]) ? $this->options['groups_fields'][$field] : $groupClass;

        if (!in_array($field, $this->options['excludes_fields'])) {
            $builder->add($field, Type\CheckboxType::class, [
                'required' => in_array($field, $this->options['required_fields']),
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->getAttribute($field, 'label'),
                'attr' => ['group' => $groupClass, 'class' => 'w-100'],
                'help' => $this->getAttribute($field, 'help'),
            ]);
        }

        $groupClass = !empty($this->options['groups_fields']['externalLink']) ? $this->options['groups_fields']['externalLink'] : $groupClass;

        if (!in_array('externalLink', $this->options['excludes_fields'])) {
            $builder->add('externalLink', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Lien externe'),
                'attr' => ['group' => $groupClass, 'class' => 'w-100'],
                'help' => $this->getAttribute('externalLink', 'help'),
            ]);
        }
    }

    /**
     * Active field.
     */
    private function getActive(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        if (!in_array($field, $this->options['excludes_fields'])) {
            $groupClass = !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-12';
            $builder->add($field, Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->getAttribute($field, 'label'),
                'attr' => ['group' => $groupClass, 'class' => 'w-100'],
                'help' => $this->getAttribute($field, 'help'),
            ]);
        }
    }

    /**
     * New pictogram field.
     */
    private function getPictogram(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $builder->add($field, Type\ChoiceType::class, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'choices' => $this->getPictograms($this->website),
            'choice_attr' => function ($dir, $key, $value) {
                return ['data-background' => strtolower($dir)];
            },
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-12',
                'class' => 'select-icons',
            ],
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Video field.
     */
    private function getVideo(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $builder->add($field, Type\TextType::class, [
            'required' => $constraints['required'],
            'label' => $this->getAttribute($field, 'label'),
            'attr' => [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-12',
            ],
            'help' => array_key_exists($field, $this->options['help_fields']) ? $this->options['help_fields'][$field] : $this->getAttribute($field, 'help'),
            'constraints' => $constraints['validators'],
        ]);
    }

    /**
     * Associated Words field.
     */
    private function getAssociatedWords(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $constraints = $this->getConstraints($field);
        $builder->add($field, Type\TextType::class, [
            'required' => $constraints['required'],
            'label' => $this->getAttribute($field, 'label'),
            'attr' => [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-12',
            ],
            'help' => $this->getAttribute($field, 'help'),
            'constraints' => $constraints['validators'],
        ]);
    }

    /**
     * Target Slug field.
     */
    private function getSlug(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $fieldType = !empty($this->options['fields_type'][$field]) ? $this->options['fields_type'][$field] : Type\TextType::class;
        $builder->add($field, $fieldType, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-12',
            ],
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Get constraints.
     */
    private function getConstraints(string $field): array
    {
        $isRequired = in_array($field, $this->options['required_fields']);
        $constraints['required'] = $isRequired;
        $constraints['validators'] = [];
        if ($isRequired) {
            $constraints['validators'][] = new Assert\NotBlank();
        }
        if (!empty($this->options['constraints_fields'][$field])) {
            $fieldConstraints = is_array($this->options['constraints_fields'][$field])
                ? $this->options['constraints_fields'][$field] : [$this->options['constraints_fields'][$field]];
            $constraints['validators'] = array_merge($constraints['validators'], $fieldConstraints);
        }

        return $constraints;
    }

    /**
     * Get label attribute.
     */
    private function getAttribute(string $field, string $type): bool|string|null
    {
        $booleanTypes = ['label'];
        $emptyAttribute = in_array($type, $booleanTypes) ? false : null;
        $optionKey = $type.'_fields';
        $attribute = $this->options[$optionKey][$field] ?? $this->getTranslationAttribute($field, $type);
        if (!$attribute) {
            $attribute = $emptyAttribute;
        }

        return $attribute;
    }

    /**
     * Get translation attribute.
     */
    private function getTranslationAttribute(string $field, string $type): ?string
    {
        $translations['title'] = [
            'label' => $this->translator->trans('Titre', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
        ];
        $translations['subTitle'] = [
            'label' => $this->translator->trans('Sous-titre', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un sous-titre', [], 'admin'),
        ];
        $translations['subTitlePosition'] = [
            'label' => $this->translator->trans('Position du sous-titre', [], 'admin'),
            'placeholder' => $this->translator->trans('Séléctionnez', [], 'admin'),
        ];
        $translations['titleForce'] = [
            'label' => $this->translator->trans('Force du titre', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
        ];
        $translations['introduction'] = [
            'label' => $this->translator->trans('Introduction', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez une introduction', [], 'admin'),
        ];
        $translations['body'] = [
            'label' => $this->translator->trans('Description', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez une description', [], 'admin'),
        ];
        $translations['targetLink'] = [
            'label' => $this->translator->trans('URL de destination', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
        ];
        $translations['targetPage'] = [
            'label' => $this->translator->trans('Page de destination', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez une page', [], 'admin'),
        ];
        $translations['targetLabel'] = [
            'label' => $this->translator->trans('Label du lien', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un label', [], 'admin'),
        ];
        $translations['newTab'] = [
            'label' => $this->translator->trans('Ouvrir dans un nouvel onglet', [], 'admin'),
        ];
        $translations['placeholder'] = [
            'label' => $this->translator->trans('Intitulé dans le champs', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
        ];
        $translations['author'] = [
            'label' => $this->translator->trans('Auteur', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un auteur', [], 'admin'),
        ];
        $translations['authorType'] = [
            'label' => $this->translator->trans("Type d'auteur", [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
        ];
        $translations['help'] = [
            'label' => $this->translator->trans('Aide', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un message', [], 'admin'),
        ];
        $translations['error'] = [
            'label' => $this->translator->trans("Message d'erreur", [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un message', [], 'admin'),
        ];
        $translations['pictogram'] = [
            'label' => $this->translator->trans('Pictogramme', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
        ];
        $translations['video'] = [
            'label' => $this->translator->trans('Lien de la vidéo', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
            'help' => $this->translator->trans('Youtube, Vimeo, Dailymotion, Facebook', [], 'admin'),
        ];
        $translations['associatedWords'] = [
            'label' => $this->translator->trans('Termes de recherche', [], 'admin'),
            'placeholder' => $this->translator->trans('Ajouter des mots', [], 'admin'),
        ];
        $translations['slug'] = [
            'label' => $this->translator->trans('Code', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez un code', [], 'admin'),
        ];
        $translations['active'] = [
            'label' => $this->translator->trans('Activer', [], 'admin'),
        ];

        return !empty($translations[$field][$type]) ? $translations[$field][$type] : null;
    }

    /**
     * Get pictograms choices.
     */
    private function getPictograms(Website $website): array
    {
        /** @var Folder $folder */
        $folder = $this->folderRepository->findOneBy([
            'website' => $website,
            'slug' => 'pictogram',
        ]);
        $markers = [];
        foreach ($folder->getMedias() as $media) {
            $markers[$media->getFilename()] = '/uploads/'.$website->getUploadDirname().'/'.$media->getFilename();
        }

        return $markers;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'fields' => ['title', 'body'],
            'excludes_fields' => [],
            'required_fields' => [],
            'constraints_fields' => [],
            'config_fields' => [],
            'groups_fields' => [],
            'attributes_fields' => [],
            'label_fields' => [],
            'placeholder_fields' => [],
            'help_fields' => [],
            'fields_data' => [],
            'fields_type' => [],
            'extra_fields' => [],
            'title_force' => false,
            'target_config' => true,
            'data_config' => false,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
