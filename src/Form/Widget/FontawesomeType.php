<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FontawesomeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FontawesomeType extends AbstractType
{
    private TranslatorInterface $translator;
    private string $projectDir;
    private array $choices;

    /**
     * FontawesomeType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->projectDir = $this->coreLocator->projectDir();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->choices = $this->getIcons();

        $resolver->setDefaults([
            'label' => $this->translator->trans('Icône', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'required' => false,
            'choices' => $this->choices['choices'],
            'dropdown_class' => 'icons-selector',
            'attr' => [
                'class' => 'select-icons',
                'group' => 'col-md-4',
            ],
            'choice_attr' => function ($icon, $key, $value) {
                if ($icon) {
                    $matches = explode(' ', $icon);
                    $category = $matches[0];
                    $icon = $this->choices['attributes'][$category][$icon];
                }
                return ['data-image' => $icon];
            },
        ]);
    }

    /**
     * Get icons.
     */
    private function getIcons(): array
    {
        $choices = [];
        $attributes = [];
        $filesystem = new Filesystem();
        $assetDirname = '/medias/icons/';
        $dirname = $this->projectDir.'/public'.$assetDirname;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);

        if ($filesystem->exists($dirname)) {
            $finder = Finder::create();
            $finder->in($dirname);
            $choices[$this->translator->trans('Séléctionnez', [], 'admin')] = '';
            foreach ($finder as $file) {
                if (!empty($file->getRelativePath())) {
                    $path = str_replace(['/', DIRECTORY_SEPARATOR], '\\', $file->getRelativePathname());
                    $matches = explode('\\', $path);
                    $icon = end($matches);
                    $matches = explode('.', $icon);
                    $extension = end($matches);
                    $matches = explode('\\', str_replace('\\'.$icon, '', $path));
                    $category = str_replace(['brands', 'main', 'duotone', 'light', 'regular', 'solid'], ['fab', 'fam', 'fad', 'fal', 'far', 'fas'], end($matches));
                    $icon = $category.' '.str_replace('.'.$extension, '', $icon);
                    $choices[$file->getRelativePath()][$path] = trim($icon);
                    $attributes[$category][$icon] = str_replace('\\', '/', $assetDirname.$path);
                }
            }
        }

        return ['choices' => $choices, 'attributes' => $attributes];
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
