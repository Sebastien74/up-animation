<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\Listing;
use App\Model\IntlModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontSearchFiltersType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontSearchFiltersType extends AbstractType
{
    private TranslatorInterface $translator;
    private array $filters;
    private Listing $listing;
    private array $products = [];
    private array $cache = [];
    private array $translations = [];

    /**
     * FrontSearchFiltersType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator) {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $request = $this->coreLocator->currentRequest();
        $formName = $builder->getForm()->getName();
        $this->filters = $request->get($formName) ? $request->get($formName) : $_GET;
        if (isset($this->filters['ajax'])) {
            unset($this->filters['ajax']);
        }

        $this->listing = $builder->getData();
        $this->products = $options['products'];

        $this->addSelect($builder, 'categories');
        $this->addSelect($builder, 'subcategories');
        $this->addSelect($builder, 'catalogs');
        $this->setFieldFeatures($builder);

        if ($this->listing->isCombineFieldsText() && $this->listing->isSearchText()) {
            $data = !empty($this->filters[Urlizer::urlize('text')]) ? $this->filters[Urlizer::urlize('text')] : null;
            $builder->add('text', Type\SearchType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'data' => $data,
                'property_path' => 'text',
                'attr' => [
                    'addon' => 'fal search',
                    'side' => 'right',
                    'placeholder' => $this->translator->trans('Recherche', [], 'front_form'),
                ],
            ]);
        }
    }

    /**
     * To add select.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function addSelect(FormBuilderInterface $builder, string $keyName): void
    {
        $configuration = $this->getFieldConfiguration($keyName);
        if ($configuration) {
            $countValueSearch = 0;
            foreach ($this->filters as $value) {
                if ($value) {
                    ++$countValueSearch;
                }
            }
            $entities = $this->products['initialResults'];
            if ('categories' === $keyName || 'subcategories' === $keyName) {
                $entities = $this->products;
            } elseif ('catalogs' === $keyName) {
                $catalogs = !empty($this->products['catalogs']) ? $this->products['catalogs'] : [];
                $entities['catalogs'] = $catalogs instanceof PersistentCollection ? $catalogs->getValues() : $catalogs;
            }
            $choices = $this->getChoices($keyName, $entities);
            if (!empty($configuration['type']) && 'select-uniq-by-categories' === $configuration['type']) {
                foreach ($choices as $key => $selectChoices) {
                    $this->addField($builder, $keyName.'_'.$key, $configuration, $selectChoices);
                }
            } elseif (count($choices) > 1) {
                $this->addField($builder, $keyName, $configuration, $choices);
            }
        }
    }

    /**
     * To set a Field of Features.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function setFieldFeatures(FormBuilderInterface $builder): void
    {
        $configuration = $this->getFieldConfiguration('features');
        if ($configuration) {
            $choices = [];
            $features = [];
            if (!$this->listing->getFeatures()->isEmpty()) {
                foreach ($this->listing->getFeatures() as $feature) {
                    $features[] = $feature->getSlug();
                }
                $choices = $this->getValues($this->products['initial']);
            } else {
                $featuresDb = $this->coreLocator->em()->getRepository(Feature::class)->findByWebsiteOrderValue($this->coreLocator->website()->entity);
                foreach ($featuresDb as $feature) {
                    $features[] = $feature->getSlug();
                    foreach ($feature->getValues() as $value) {
                        $choices = $this->getChoiceValue($value, $choices);
                    }
                }
            }
            if ($choices) {
                if (!empty($configuration['multiple'])) {
                    $this->addField($builder, 'values', $configuration, $choices);
                } else {
                    if (!empty($features) && !$this->listing->getFeatures()->isEmpty()) {
                        foreach ($this->listing->getFeatures() as $feature) {
                            if (!empty($choices[$feature->getSlug()])) {
                                $this->addField($builder, $feature->getSlug(), $configuration, $choices[$feature->getSlug()]);
                            }
                        }
                    } else {
                        foreach ($choices as $name => $selectChoices) {
                            $addField = in_array($name, $features);
                            if ($addField && !empty($selectChoices['choices']) && count($selectChoices['choices']) > 1) {
                                $this->addField($builder, $name, $configuration, $selectChoices);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * To add field.
     */
    private function addField(FormBuilderInterface $builder, string $name, array $configuration, array $choices): void
    {
        $formName = $builder->getForm()->getName();
        $data = !empty($this->filters[$formName][Urlizer::urlize($name)]) ? $this->filters[$formName][Urlizer::urlize($name)] : (isset($configuration['multiple']) && $configuration['multiple'] ? [] : null);
        $data = !$data && !empty($this->filters[Urlizer::urlize($name)]) ? $this->filters[Urlizer::urlize($name)] : $data;
        $data = 1 == $data ? true : (0 == $data ? false : $data);
        $fieldType = !empty($choices['fieldType']) ? $choices['fieldType'] : Type\ChoiceType::class;
        $choicesValues = !empty($choices['choices']) ? $choices['choices'] : (!isset($choices['fieldType']) ? $choices : []);
        $label = !empty($choices['label']) ? $choices['label'] : (!empty($this->translations[$name]) ? $this->translations[$name] : false);
        $label = 'catalogs' === $name && !$label ? $this->translator->trans('Catalogue', [], 'front_form') : $label;
        $placeholder = isset($configuration['placeholder']) && $configuration['placeholder'] ? $this->translator->trans('Sélectionnez', [], 'front_form') : false;

        $arguments['required'] = false;
        $arguments['data'] = $data;
        $arguments['label'] = !$this->listing->isDisplayLabel() && Type\ChoiceType::class === $fieldType ? false : $label;
        $arguments['mapped'] = false;

        if (Type\ChoiceType::class === $fieldType) {
            $arguments['placeholder'] = !$this->listing->isDisplayLabel() ? $label : $placeholder;
            if ('categories' == $name && !$this->listing->isDisplayLabel()) {
                $arguments['placeholder'] = $this->translator->trans('Catégorie', [], 'front_form');
            }
            if ('subcategories' == $name && !$this->listing->isDisplayLabel()) {
                $arguments['placeholder'] = $this->translator->trans('Sous-catégorie', [], 'front_form');
            }
            $expanded = isset($configuration['expanded']) && true === $configuration['expanded'];
            if ($data && !$expanded) {
                $arguments['placeholder'] = $this->translator->trans('Supprimer la sélection', [], 'front_form');
            } elseif ($expanded) {
                $arguments['placeholder'] = $this->translator->trans('Tout', [], 'front_form');
                $arguments['row_attr'] = ['class' => 'disabled-floating d-lg-flex align-items-lg-start'];
            }
            $selectedClass = $data ? ' selected' : '';
            $arguments['attr']['class'] = isset($configuration['multiple']) && !$configuration['multiple'] ? 'select-search'.$selectedClass : $selectedClass;
            $arguments['attr']['class'] = $expanded ? 'form-check form-check-inline '.$arguments['attr']['class'] : $arguments['attr']['class'];
            $arguments['attr']['group'] = isset($configuration['multiple']) && $configuration['multiple'] ? 'col-md-12' : 'p-0 col-md-12';
            $arguments['attr']['data-floating'] = false;
            $arguments['label_class'] = '';
            $arguments['display'] = $configuration['display'];
        } else {
            $arguments['attr']['group'] = 'col-md-4';
        }

        if ($choicesValues) {
            $arguments['choices'] = $choicesValues;
        }
        if (isset($configuration['multiple'])) {
            $arguments['multiple'] = $configuration['multiple'];
        }
        if (isset($configuration['expanded'])) {
            $arguments['expanded'] = $configuration['expanded'];
        }
        if (isset($arguments['expanded']) && (bool) $arguments['expanded'] !== true || empty($arguments['expanded'])) {
            $arguments['attr']['reset-btn'] = true;
            $arguments['attr']['data-display'] = $arguments['display'];
        }
        if ('subcategories' === $name && isset($configuration['type']) && 'radios-by-categories' === $configuration['type']) {
            $arguments['attr']['radios-groups'] = true;
        }

        $builder->add(Urlizer::urlize($name), $fieldType, $arguments);
    }

    /**
     * To get choices.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function getChoices(string $keyName, array $fields): array
    {
        $choices = [];
        if (!empty($fields[$keyName])) {
            foreach ($fields[$keyName] as $field) {
                $intl = IntlModel::fromEntity($field, $this->coreLocator, false);
                $label = $intl->title ?: $field->getAdminName();
                $label = html_entity_decode($label);
                if ('subcategories' === $keyName) {
                    $category = $field->getCatalogcategory();
                    $this->cache[Category::class][$category->getId()] = $categoryIntl = !empty($this->cache[Category::class][$category->getId()])
                        ? $this->cache[Category::class][$category->getId()] : IntlModel::fromEntity($category, $this->coreLocator, false);
                    $categoryLabel = $categoryIntl->title ?: $category->getAdminName();
                    $choices[$categoryLabel][ucfirst(trim($label))] = $field->getSlug();
                } else {
                    $choices[ucfirst(trim($label))] = $field->getSlug();
                }
            }
        }

        // Alpha order
        $collator = new \Collator($this->coreLocator->locale());
        if ('subcategories' === $keyName) {
            foreach ($choices as &$subArray) {
                uksort($subArray, function ($a, $b) use ($collator) {
                    return $collator->compare($a, $b);
                });
            }
            unset($subArray);
            uksort($choices, function ($a, $b) use ($collator) {
                return $collator->compare($a, $b);
            });
        } else {
            uksort($choices, function ($a, $b) use ($collator) {
                return $collator->compare($a, $b);
            });
        }

        return $choices;
    }

    /**
     * To get values.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function getValues(array $fields): array
    {
        $choices = [];

        if (!empty($fields['products-values'])) {
            foreach ($fields['products-values'] as $values) {
                foreach ($values as $productValue) {
                    $choices = $this->getChoiceValue($productValue->getValue(), $choices);
                }
            }
        }

        foreach ($choices as $key => $config) {
            uksort($config['choices'], function ($a, $b) use ($config) {
                return $config['valuesPosition'][$a] <=> $config['valuesPosition'][$b];
            });
        }

        return $choices;
    }

    /**
     * To get choice value.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function getChoiceValue(FeatureValue $value, array $choices): array
    {
        if ($value->getSlug()) {
            $feature = $value->getCatalogfeature();
            $intlFeature = IntlModel::fromEntity($feature, $this->coreLocator, false);
            $fieldLabel = $intlFeature->title ?: $feature->getAdminName();
            $fieldType = $feature->isAsBool() ? Type\CheckboxType::class : Type\ChoiceType::class;
            $featureSlug = $feature->getSlug();
            $this->translations[$featureSlug] = $intlFeature->title
                ? ucfirst(trim($intlFeature->title)) : ucfirst(trim($feature->getAdminName()));
            $intlValue = IntlModel::fromEntity($value, $this->coreLocator, false);
            $valueLabel = $intlValue->title ?: $value->getAdminName();
            $choices[$featureSlug]['label'] = $fieldLabel;
            $choices[$featureSlug]['fieldType'] = $fieldType;
            $choices[$featureSlug]['feature'] = $feature;
            if (Type\ChoiceType::class === $fieldType) {
                $choices[$featureSlug]['choices'][ucfirst(trim($valueLabel))] = $value->getSlug();
                $choices[$featureSlug]['valuesPosition'][ucfirst(trim($valueLabel))] = $value->getPosition();
            }
            ksort($choices);
        }

        return $choices;
    }

    /**
     * Get field configuration.
     */
    private function getFieldConfiguration(string $propertyName, array $choices = []): array
    {
        $getter = 'getSearch'.ucfirst($propertyName);
        $property = method_exists($this->listing, $getter) ? $this->listing->$getter() : null;
        if ('all' == $this->listing->getDisplay()) {
            return ['multiple' => false, 'expanded' => false, 'display' => 'classic', 'placeholder' => true];
        } elseif ('select-multiple' === $property) {
            return ['multiple' => true, 'expanded' => false, 'display' => 'classic', 'placeholder' => true];
        } elseif ($property && str_contains($property, 'select-uniq')) {
            return ['multiple' => false, 'expanded' => false, 'display' => 'classic', 'placeholder' => true, 'type' => $property];
        } elseif ($property && str_contains($property, 'radios')) {
            return ['multiple' => false, 'expanded' => true, 'display' => false, 'placeholder' => false, 'type' => $property];
        } elseif ('checkboxes' === $property) {
            return ['multiple' => true, 'expanded' => true, 'display' => false, 'placeholder' => false];
        } elseif (!empty($choices['feature']) && $choices['feature'] instanceof Feature && !$choices['feature']->isAsBool()) {
            return ['multiple' => false, 'expanded' => false, 'display' => 'classic', 'placeholder' => true];
        }

        return [];
    }

    public function configureOptions(OptionsResolver $resolver, $options = []): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'filters' => null,
            'custom_filters' => [],
            'translation_domain' => 'front_form',
            'products' => [],
            'website' => null,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
