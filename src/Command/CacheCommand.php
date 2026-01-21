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
            $cacheDirname = $this->kernel->getCacheDir();
            $tmpDirname = $this->kernel->getProjectDir().'/var/cache/__'.$this->kernel->getEnvironment().'_'.uniqid('', true);
            $tmpDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tmpDirname);
            if ($filesystem->exists($tmpDirname)) {
                $filesystem->remove($tmpDirname);
            }
            $filesystem->rename($cacheDirname, $tmpDirname);
            if (!$onlyRename && $filesystem->exists($cacheDirname)) {
                $filesystem->remove($tmpDirname);
            }
            return 'Cache successfully cleared.';
        }

        return $this->execute([
            'command' => 'cache:clear',
            '--env' => $this->kernel->getEnvironment(),
        ]);
    }
}
