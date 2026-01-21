<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ContentConfigType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContentConfigType
{
    private TranslatorInterface $translator;
    private array $options = [];

    /**
     * ContentConfigType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * To add fields.
     */
    public function add(FormBuilderInterface $builder, array $options): void
    {
        $this->options = $options;
        foreach ($options['fields'] as $key => $name) {
            $field = is_int($key) ? $name : $key;
            $getter = 'get'.ucfirst($field);
            if (method_exists($this, $getter)) {
                $this->$getter($builder, $field, $options);
            }
        }
    }

    /**
     * Font weight field.
     */
    private function getFontWeight(FormBuilderInterface $builder, string $field): void
    {
        $this->getFontWeightField($builder, $field);
    }

    /**
     * Font weight field.
     */
    private function getFontWeightSecondary(FormBuilderInterface $builder, string $field): void
    {
        $this->getFontWeightField($builder, $field);
    }

    /**
     * Font weight field.
     */
    private function getFontWeightField(FormBuilderInterface $builder, string $field): void
    {
        $weights = [900, 800, 700, 600, 500, 400, 300, 200, 100];
        $choices = [];
        foreach ($weights as $weight) {
            $choices[$weight] = $weight;
        }

        $builder->add($field, Type\ChoiceType::class, [
            'required' => false,
            'display' => 'search',
            'label' => !empty($this->options['labels'][$field]) ? $this->options['labels'][$field] : $this->translator->trans('Gras', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => [
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-2',
                'class' => 'select-icons',
            ],
            'choices' => $choices,
            'choice_attr' => function ($weight, $key, $value) {
                return ['class' => 'fw-'.$weight, 'data-fw' => $weight];
            },
        ]);
    }

    /**
     * Font size field.
     */
    private function getFontSize(FormBuilderInterface $builder, string $field): void
    {
        $sizes = [
            'xs' => '19',
            'sm' => '26',
            'md' => '35',
            'lg' => '45',
            'xl' => '55',
            'xxl' => '75'
        ];
        $choices = [];
        foreach ($sizes as $size => $pixels) {
            $label = strtoupper($size);
            if ('undefined' !== $pixels) {
                $label .= ' ('.$pixels.'px)';
            }
            $choices[$label] = $size;
        }
        $builder->add('fontSize', Type\ChoiceType::class, [
            'required' => false,
            'display' => 'search',
            'label' => !empty($this->options['labels'][$field]) ? $this->options['labels'][$field] : $this->translator->trans('Taille de la police', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => [
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-2',
                'class' => 'select-icons',
            ],
            'choices' => $choices,
            'choice_attr' => function ($size, $key, $value) {
                return ['data-fz' => $size];
            },
        ]);
    }

    /**
     * Font family field.
     */
    private function getColor(FormBuilderInterface $builder, string $field): void
    {
        $builder->add('color', AppColorType::class, [
            'label' => !empty($this->options['labels'][$field]) ? $this->options['labels'][$field] : $this->translator->trans('Couleur', [], 'admin'),
            'attr' => [
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-2',
                'class' => 'select-icons',
            ],
        ]);
    }

    /**
     * Font italic field.
     */
    private function getItalic(FormBuilderInterface $builder, string $field): void
    {
        $builder->add('italic', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => !empty($this->options['labels'][$field]) ? $this->options['labels'][$field] : $this->translator->trans('En italique', [], 'admin'),
            'attr' => [
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field].' d-flex align-items-end' : 'col-md-2 d-flex align-items-end',
                'class' => 'w-100',
            ],
        ]);
    }

    /**
     * Font uppercase field.
     */
    private function getUppercase(FormBuilderInterface $builder, string $field): void
    {
        $builder->add('uppercase', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => !empty($this->options['labels'][$field]) ? $this->options['labels'][$field] : $this->translator->trans('En majuscule', [], 'admin'),
            'attr' => [
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field].' d-flex align-items-end' : 'col-md-2 d-flex align-items-end',
                'class' => 'w-100',
            ],
        ]);
    }

    /**
     * Font family field.
     */
    private function getFontFamily(FormBuilderInterface $builder, string $field): void
    {
        $builder->add('fontFamily', Type\ChoiceType::class, [
            'required' => false,
            'display' => 'search',
            'label' => !empty($this->options['labels'][$field]) ? $this->options['labels'][$field] : $this->translator->trans('Famille de police', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => [
                'group' => !empty($this->options['fields'][$field]) ? $this->options['fields'][$field] : 'col-md-3',
                'class' => 'select-icons',
            ],
            'choices' => $this->getFonts(),
        ]);
    }

    /**
     * Get Fonts library.
     */
    private function getFonts(): array
    {
        $fonts = [];

        $fontsDirname = $this->coreLocator->projectDir().'/assets/scss/front/'.$this->coreLocator->website()->configuration->template.'/';
        $fontsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fontsDirname);
        $filesystem = new Filesystem();
        if ($filesystem->exists($fontsDirname)) {
            $finder = Finder::create();
            $finder->files()->in($fontsDirname)->name('variables.scss');
            foreach ($finder as $file) {
                $pattern = '/\$theme-fonts:\s*(.*?);/s';
                preg_match_all($pattern, $file->getContents(), $matches);
                foreach ($matches[1] as $match) {
                    $matchesRules = explode("'font-family':", $match);
                    foreach ($matchesRules as $matcheRule) {
                        if (str_contains($matcheRule, '$font')) {
                            $matchesVars = explode(',', $matcheRule);
                            $var = trim($matchesVars[0]);
                            $varPattern = '/\\'.$var.':\s*(.*?);/s';
                            preg_match_all($varPattern, $file->getContents(), $matchesVars);
                            $matchesFont = explode(',', $matchesVars[1][0]);
                            $fonts[str_replace(["'", '"'], '', $matchesFont[0])] = str_replace('$font-', '', $var);
                        }
                    }
                }
            }
        }

        return $fonts;
    }
}
