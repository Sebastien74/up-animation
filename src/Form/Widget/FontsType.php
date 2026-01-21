<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FontsType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FontsType extends AbstractType
{
    private string $projectDir;

    /**
     * FontsType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->projectDir = $this->coreLocator->projectDir();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'expanded' => false,
            'display' => 'search',
            'multiple' => true,
            'choices' => $this->getFonts(),
        ]);
    }

    /**
     * Get Fonts library.
     */
    private function getFonts(): array
    {
        $fontsDirname = $this->projectDir.'/assets/lib/fonts';
        $fontsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fontsDirname);
        $finder = Finder::create();
        $finder->in($fontsDirname)->name('*.scss');

        $fonts = [];
        foreach ($finder as $file) {
            if (!$file->isDir()) {
                $filename = str_replace(['.scss'], [''], $file->getFilename());
                $fonts[ucfirst(str_replace('-', ' ', $filename))] = strtolower($filename);
            }
        }

        ksort($fonts);

        return $fonts;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
