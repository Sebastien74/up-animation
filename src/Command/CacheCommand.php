<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Filesystem\Filesystem;

/**
 * CacheCommand.
 *
 * To execute cache commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CacheCommand extends BaseCommand
{
    /**
     * Execute cache:clear --env.
     */
    public function clear(bool $asFilesystem = false, bool $onlyRename = false): string
    {
        if ($asFilesystem) {

            $filesystem = new Filesystem();
            $env = $this->kernel->getEnvironment();
            $cacheDirname = $this->kernel->getCacheDir();
            $cacheParentDir = dirname($cacheDirname);

            // Find an available temporary name by adding underscores recursively
            $prefix = '_';
            $tmpDirname = $cacheParentDir . DIRECTORY_SEPARATOR . $prefix . $env;
            while ($filesystem->exists($tmpDirname)) {
                $prefix .= '_';
                $tmpDirname = $cacheParentDir . DIRECTORY_SEPARATOR . $prefix . $env;
            }

            if ($filesystem->exists($cacheDirname)) {
                $filesystem->rename($cacheDirname, $tmpDirname);
            }

            if (!$onlyRename) {
                $finder = new \Symfony\Component\Finder\Finder();
                if ($filesystem->exists($cacheParentDir)) {
                    $finder->directories()->in($cacheParentDir)->depth('== 0')->name('/^_+'.preg_quote($env, '/').'$/');
                    foreach ($finder as $dir) {
                        $filesystem->remove($dir->getRealPath());
                    }
                }
            }

            return 'Cache successfully cleared.';
        }

        return $this->execute([
            'command' => 'cache:clear',
            '--env' => $this->kernel->getEnvironment(),
        ]);
    }
}
