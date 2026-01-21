<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Filesystem\Filesystem;

/**
 * AssetsCommand.
 *
 * To execute assets commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AssetsCommand extends BaseCommand
{
    /**
     * Execute assets:install --symlink --relative web.
     */
    public function install(): string
    {
        $dirname = $this->kernel->getProjectDir().'/public/bundles/';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $filesystem = new Filesystem();
        if ($filesystem->exists($dirname)) {
            $filesystem->remove($dirname);
        }

        return $this->execute([
            'command' => 'assets:install',
            '--symlink' => true,
            '--relative' => 'public',
        ]);
    }
}
