<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core\Color;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * ManifestRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ManifestRuntime implements RuntimeExtensionInterface
{
    /**
     * ManifestRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly ColorRuntime $colorRuntime
    ) {
    }

    /**
     * To get web manifest.
     */
    public function manifest(WebsiteModel $website): string
    {
        $filename = 'manifest.webmanifest.'.$_ENV['APP_ENV'].'.'.$website->slug.'.json';
        $publicDirname = $this->coreLocator->projectDir().'/public/';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicDirname.$filename);
        $filesystem = new Filesystem();

        if (!$filesystem->exists($dirname)) {
            $icons = [];
            $name = $website->information->intl->title;
            $logos = $website->configuration->logos;
            $theme = $this->colorRuntime->color('favicon', $website, 'webmanifest-theme');
            $background = $this->colorRuntime->color('favicon', $website, 'webmanifest-background');
            $files = ['web-app-manifest-192x192' => '192x192', 'web-app-manifest-512x512' => '512x512'];
            foreach ($files as $key => $size) {
                if (!empty($logos[$key])) {
                    $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $publicDirname.$logos[$key]);
                    if ($filesystem->exists($fileDirname)) {
                        $file = new File($fileDirname);
                        $icons[] = [
                            'src' => $logos[$key],
                            'sizes' => $size,
                            'type' => 'image/'.$file->getExtension(),
                            'purpose' => 'maskable',
                        ];
                    }
                }
            }
            $data = [
                'prefer_related_applications' => false,
                'short_name' => $name,
                'name' => $name,
                'icons' => $icons,
                'display' => 'standalone',
                'start_url' => '/',
                'scope' => '/',
                'theme_color' => $theme instanceof Color && $theme->isActive() ? $theme->getColor() : '#ffffff',
                'background_color' => $background instanceof Color && $background->isActive() ? $background->getColor() : '#ffffff',
                'description' => $name,
            ];
            file_put_contents($dirname, json_encode($data));
        }

        return $filename;
    }
}
