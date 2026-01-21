<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Filesystem\Filesystem;

/**
 * JsRoutingCommand.
 *
 * To execute fos js-routing commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class JsRoutingCommand extends BaseCommand
{
    /**
     * Execute fos:js-routing:dump.
     */
    public function dump(?string $filename = null, bool $all = false): string
    {
        $output = $this->execute([
            'command' => 'fos:js-routing:dump',
            '--format' => 'json',
            '--target' => 'js/fos_js_routes.json',
        ]);

        if ($filename) {
            $this->generateFile($filename, $all);
        } else {
            $this->generateFile('fos_js_routes_front', false);
            $this->generateFile('fos_js_routes_admin', true);
        }

        return $output;
    }

    /**
     * Execute fos:js-routing:debug.
     */
    public function debug(): string
    {
        return $this->execute([
            'command' => 'fos:js-routing:debug',
        ]);
    }

    /**
     * To generate front json file.
     */
    private function generateFile(?string $filename = null, bool $all = false): void
    {
        $filename = $filename ? $filename : 'fos_js_routes_front';
        $adminAllowed = ['admin_user_switcher'];
        $jsRoutingDirname = $this->kernel->getProjectDir().'/public/js/fosjsrouting/';
        $jsRoutingDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $jsRoutingDirname);
        $jsRoutingFileDirname = $this->kernel->getProjectDir().'/public/js/fos_js_routes.json';
        $jsRoutingFileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $jsRoutingFileDirname);
        $filesystem = new Filesystem();

        if (!$filesystem->exists($jsRoutingDirname)) {
            $filesystem->mkdir($jsRoutingDirname, 0777);
        }

        if ($filesystem->exists($jsRoutingFileDirname)) {
            $content = json_decode(file_get_contents($jsRoutingFileDirname));
            foreach ($content->routes as $routeName => $params) {
                if ($all || in_array($routeName, $adminAllowed) || !str_contains($routeName, 'admin_') && !str_contains($routeName, 'cache_clear')) {
                    $routes['routes'][$routeName] = $params;
                }
            }

            $routes['base_url'] = $content->base_url;
            $routes['routes'] = (object) $routes['routes'];
            $routes['prefix'] = $content->prefix;
            $routes['host'] = $content->host;
            $routes['port'] = $content->port;
            $routes['scheme'] = $content->scheme;
            $routes['locale'] = $content->locale;

            file_put_contents($jsRoutingDirname.$filename.'.json', json_encode((object) $routes));
        }
    }
}
