<?php

declare(strict_types=1);

namespace App\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\RouteCollection;

/**
 * ExtraLoader.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExtraLoader extends Loader
{
    private bool $isLoaded = false;

    /**
     * ExtraLoader constructor.
     */
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    public function load($resource, $type = null): ?RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        $filesystem = new Filesystem();
        $moduleDirname = $this->projectDir.'/vendor/sfcms';
        $moduleDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $moduleDirname);

        if ($filesystem->exists($moduleDirname)) {
            $routes = new RouteCollection();
            $finder = Finder::create();
            $finder->in($moduleDirname)->name('routes.yaml');
            foreach ($finder as $file) {
                $importedRoutes = $this->import($file->getPathname(), 'yaml');
                $routes->addCollection($importedRoutes);
            }
            $this->isLoaded = true;
            return $routes;
        }

        return null;
    }

    public function supports($resource, $type = null): bool
    {
        return 'extra' === $type;
    }
}