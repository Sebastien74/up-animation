<?php

declare(strict_types=1);

namespace App\Service\Core;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouterInterface;

/**
 * CacheService.
 *
 * Manage app cache.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class CacheService implements CacheServiceInterface
{
    /**
     * CacheService constructor.
     */
    public function __construct(
        private string $cacheDir,
        private RouterInterface $router,
    ) {
    }

    /**
     * To generate routes cache file.
     */
    public function generateRoutes(): void
    {
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->cacheDir.'/routes.cache');
        $filesystem = new Filesystem();
        if (!$filesystem->exists($dirname)) {
            $cacheRoutes = [];
            $routes = $this->router->getRouteCollection()->all();
            foreach ($routes as $name => $route) {
                $isMainRequest = true;
                $defaults = $route->getDefaults();
                if (isset($defaults['_controller'])) {
                    $options = is_object($route) && method_exists($route, 'getOptions') ? $route->getOptions() : [];
                    $isMainRequest = $options['isMainRequest'] ?? true;
                }
                $cacheRoutes[$name] = ['isMainRequest' => $isMainRequest];
            }
            $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
            $cache->warmUp($cacheRoutes);
        }
    }
}