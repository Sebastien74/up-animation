<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Form\Type\Seo\SeoBasicType;
use App\Form\Validator\UniqUrl;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\IconRuntime;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UrlType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UrlType extends AbstractType
{
    private TranslatorInterface $translator;
    private array $options = [];

    /**
     * UrlType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly IconRuntime $iconRuntime,
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->options = $options;

        foreach ($options['fields'] as $key => $name) {
            $field = is_int($key) ? $name : $key;
            $groupClass = is_int($key) ? 'col-12' : $name;
            $getter = 'get'.ucfirst($field);
            if (method_exists($this, $getter)) {
                $this->$getter($builder, $field, $groupClass);
            }
        }

        if ($options['display_seo']) {
            $builder->add('seo', SeoBasicType::class);
        }
    }

    /**
     * Code field.
     */
    private function getCode(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $builder->add($field, Type\TextType::class, [
            'required' => in_array($field, $this->options['required_fields']),
            'label' => $this->getAttribute($field, 'label'),
            'attr' => [
                'placeholder' => $this->getAttribute($field, 'placeholder'),
                'code' => 'code',
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : $groupClass,
            ],
            'constraints' => [new UniqUrl()],
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Code field.
     */
    private function getHideInSitemap(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $builder->add($field, Type\CheckboxType::class, [
            'required' => in_array($field, $this->options['required_fields']),
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->getAttribute($field, 'label'),
            'attr' => ['group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : $groupClass, 'class' => 'w-100'],
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Code field.
     */
    private function getAsIndex(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $builder->add($field, Type\CheckboxType::class, [
            'required' => in_array($field, $this->options['required_fields']),
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->getAttribute($field, 'label'),
            'attr' => ['group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : $groupClass, 'class' => 'w-100'],
            'help' => $this->getAttribute($field, 'help'),
        ]);
    }

    /**
     * Code field.
     */
    private function getOnline(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $builder->add($field, Type\ChoiceType::class, [
            'label' => false,
            'choices' => [
                $this->translator->trans('En ligne', [], 'admin') => true,
                $this->translator->trans('Hors ligne', [], 'admin') => false,
            ],
            'attr' => ['class' => 'select-icons'],
            'choice_attr' => function ($boolean, $key, $value) {
                if (true === $boolean) {
                    return [
                        'data-svg' => $this->iconRuntime->icon('far check', 17, 17, 'me-2 success', [], null, false),
                        'data-text' => true,
                    ];
                } else {
                    return [
                        'data-svg' => $this->iconRuntime->icon('far ban', 17, 17, 'me-2 danger', [], null, false),
                        'data-text' => true,
                    ];
                }
            },
        ]);
    }

    /**
     * Code index Page field.
     */
    private function getIndexPage(FormBuilderInterface $builder, string $field, ?string $groupClass = null): void
    {
        $builder->add($field, EntityType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->translator->trans("Page de l'index", [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'class' => Page::class,
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);
    }

    /**
     * Get label attribute.
     */
    private function getAttribute(string $field, string $type): bool|string|null
    {
        $booleanTypes = ['hideInSitemap', 'online', 'asIndex'];
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
        $translations['code'] = [
            'label' => $this->translator->trans('URL', [], 'admin'),
            'placeholder' => $this->translator->trans('Saisissez une URL', [], 'admin'),
        ];
        $translations['hideInSitemap'] = [
            'label' => $this->translator->trans('Cacher dans plan de site', [], 'admin'),
        ];
        $translations['asIndex'] = [
            'label' => $this->translator->trans('Robots index', [], 'admin'),
        ];

        return !empty($translations[$field][$type]) ? $translations[$field][$type] : null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Url::class,
            'fields' => ['code', 'hideInSitemap', 'online'],
            'required_fields' => [],
            'label_fields' => [],
            'help_fields' => [],
            'display_seo' => false,
            'translation_domain' => 'admin',
        ]);
    }
}
