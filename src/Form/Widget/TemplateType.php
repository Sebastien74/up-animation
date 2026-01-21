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
 * TemplateType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TemplateType extends AbstractType
{
    private TranslatorInterface $translator;
    private string $projectDir;

    /**
     * TemplateType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->projectDir = $this->coreLocator->projectDir();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Template', [], 'admin'),
            'choices' => $this->getTemplates(),
            'display' => 'search',
            'translation_domain' => 'admin',
        ]);
    }

    /**
     * Get front templates.
     */
    private function getTemplates(): array
    {
        $labels = [
            'default' => $this->translator->trans('Défaut', [], 'admin'),
        ];

        $templates = [];
        $frontDir = $this->projectDir.'/templates/front/';
        $frontDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $frontDir);
        $finder = Finder::create();
        $finder->files()->in($frontDir);
        foreach ($finder as $file) {
            $explodePath = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
            if (is_dir($frontDir.DIRECTORY_SEPARATOR.$file->getRelativePath()) && count($explodePath) >= 1 && !empty($explodePath[0]) && !in_array($explodePath[0], $templates)) {
                $label = !empty($labels[$explodePath[0]]) ? $labels[$explodePath[0]] : ucfirst($explodePath[0]);
                $templates[$label] = $explodePath[0];
            }
        }

        return $templates;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
