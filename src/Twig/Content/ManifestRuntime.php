<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core\Color;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
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
    public function manifest(\App\Model\Core\WebsiteModel $website): string
    {
        $request = $this->coreLocator->request();
        $filename = 'manifest.webmanifest.'.$_ENV['APP_ENV'].'.'.$website->slug.'.json';
        $publicDirname = $this->coreLocator->projectDir().'/public/';
        $dirname = $publicDirname.$filename;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem = new Filesystem();
        $content = $filesystem->exists($dirname) ? file_get_contents($dirname) : false;
        $update = $request instanceof Request && $content && !str_contains($content, $request->getHost());

        if (!$filesystem->exists($dirname) || $update) {
            if ($request instanceof Request) {
                $icons = [];
                $schemeAndHttpHost = $request->getSchemeAndHttpHost();
                $uploadDirname = $website->uploadDirname;
                $information = $website->information;
                $name = $information->intl->title;
                $logos = $website->configuration->logos;
                $theme = $this->colorRuntime->color('favicon', $website, 'webmanifest-theme');
                $background = $this->colorRuntime->color('favicon', $website, 'webmanifest-background');
                $files = ['android-chrome-144x144' => '144x144', 'android-chrome-192x192' => '192x192', 'android-chrome-512x512' => '512x512', 'mask-icon' => '196x196'];
                foreach ($files as $fileName => $size) {
                    if (!empty($logos[$fileName])) {
                        $configFilename = $fileName;
                        $fileDirname = $publicDirname.$logos[$fileName];
                        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
                        if ($filesystem->exists($fileDirname)) {
                            $file = new File(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname));
                            if (!preg_match('/'.$fileName.'/', $fileDirname)) {
                                $matches = explode(DIRECTORY_SEPARATOR, $fileDirname);
                                $fileName = str_replace('.'.$file->getExtension(), '', end($matches));
                            }
                            $values = [];
                            $values['src'] = $schemeAndHttpHost.'/uploads/'.$uploadDirname.'/'.$fileName.'.'.$file->getExtension();
                            $values['sizes'] = $size;
                            $values['type'] = 'image/'.$file->getExtension();
                            if ('mask-icon' === $configFilename) {
                                $values['purpose'] = 'any maskable';
                            }
                            $icons[] = $values;
                        }
                    }
                }
                $data = [
                    'prefer_related_applications' => true,
                    'short_name' => $name,
                    'name' => $name,
                    'icons' => $icons,
                    'display' => 'standalone', /* or fullscreen */
                    'start_url' => '/',
                    'scope' => '/',
                    'theme_color' => $theme instanceof Color && $theme->isActive() ? $theme->getColor() : '#ffffff',
                    'background_color' => $background instanceof Color && $background->isActive() ? $background->getColor() : '#ffffff',
                    'description' => $name,
                ];
                file_put_contents($dirname, json_encode($data));
            }
        }

        return $filename;
    }
}
