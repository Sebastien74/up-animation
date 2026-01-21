<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Model\Core\ConfigurationModel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * FontsRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FontsRuntime implements RuntimeExtensionInterface
{
    /**
     * FontsRuntime constructor.
     */
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Check if as google font.
     */
    public function asGoogleFont(string $template): bool
    {
        $fontsDirname = $this->projectDir.'/assets/scss/front/'.$template.'/';
        $fontsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fontsDirname);
        $filesystem = new Filesystem();
        if ($filesystem->exists($fontsDirname)) {
            $finder = Finder::create();
            $finder->files()->in($fontsDirname)->name('fonts.scss');
            foreach ($finder as $file) {
                if (str_contains($file->getContents(), 'google')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get font family.
     */
    private function fontFamily(string $fontName): ?string
    {
        $fonts['google-barlow'] = "'Barlow', sans-serif";
        $fonts['google-bebasneue'] = "'Bebas Neue', cursive";
        $fonts['google-cabin'] = "'Cabin', sans-serif";
        $fonts['google-catamaran'] = "'Catamaran', sans-serif";
        $fonts['google-cormorant-garamond'] = "'Cormorant Garamond', serif";
        $fonts['google-firasans'] = "'Fira Sans', sans-serif";
        $fonts['google-glegoo'] = "'Glegoo', serif";
        $fonts['google-josefin-sans'] = "'Josefin Sans', sans-serif";
        $fonts['google-kanit'] = "'Kanit', sans-serif";
        $fonts['google-lato'] = "'Lato', sans-serif";
        $fonts['google-montserrat'] = "'Montserrat', sans-serif";
        $fonts['google-mplusrounded'] = "'M PLUS Rounded 1c', sans-serif";
        $fonts['google-oldstandard'] = "'Old Standard TT', serif";
        $fonts['google-opensans'] = "'Open Sans', sans-serif";
        $fonts['google-philosopher'] = "'Philosopher', sans-serif";
        $fonts['google-playfairdisplay'] = "'Playfair Display', sans-serif";
        $fonts['google-poppins'] = "'Poppins', sans-serif";
        $fonts['google-roboto'] = "'Roboto', sans-serif";

        $fontFamily = !empty($fonts[$fontName]) ? $fonts[$fontName] : null;
        if (!$fontFamily) {
            $fontFamily = "'".ucfirst($fontName)."', sans-serif";
        }

        return $fontFamily;
    }

    /**
     * Get front fonts.
     */
    public function appAdminFonts(ConfigurationModel $configuration): string
    {
        $fonts = '';
        $fontsDirname = $this->projectDir.'/assets/scss/front/'.$configuration->template.'/';
        $fontsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fontsDirname);
        $filesystem = new Filesystem();
        if ($filesystem->exists($fontsDirname)) {
            $finder = Finder::create();
            $finder->files()->in($fontsDirname)->name('fonts.scss');
            foreach ($finder as $file) {
                $pattern = '/\$fontFamily:\s*(.*?);/s';
                preg_match_all($pattern, $file->getContents(), $matches);
                foreach ($matches[1] as $match) {
                    $matchesName = explode(',', $match);
                    $fonts .= ucfirst(str_replace(['"', "'"], '', $matchesName[0])).'='.str_replace(['"', "'"], '', $match).'; ';
                }
            }
        }
        return rtrim($fonts, ', ');
    }
}
