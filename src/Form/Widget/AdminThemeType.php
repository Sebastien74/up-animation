<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AdminThemeType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AdminThemeType extends AbstractType
{
    private TranslatorInterface $translator;
    private string $projectDir;

    /**
     * AdminThemeType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->projectDir = $this->coreLocator->projectDir();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Admin thème', [], 'admin'),
            'placeholder' => $this->translator->trans('Séléctionnez', [], 'admin'),
            'required' => false,
            'choices' => $this->getTemplates(),
            'display' => 'search',
            'translation_domain' => 'admin',
        ]);
    }

    /**
     * Get admin templates.
     */
    private function getTemplates(): array
    {
        $labels = [
            'clouds' => $this->translator->trans('Nuages', [], 'admin'),
            'felix' => $this->translator->trans('Félix', [], 'admin'),
            'default' => $this->translator->trans('Défaut', [], 'admin'),
        ];

        $themes = [];
        $dirname = $this->projectDir.'/assets/scss/admin/themes';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $finder = Finder::create();
        $finder->in($dirname);

        $themes['Anonyme'] = null;
        foreach ($finder as $file) {
            $filename = $file->getFilename();
            if (str_contains($filename, '-vendor')) {
                $theme = str_replace('-vendor.scss', '', $filename);
                $label = !empty($labels[$theme]) ? $labels[$theme] : ucfirst($theme);
                $themes[$label] = $theme;
            }
        }

        return $themes;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
