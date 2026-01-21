<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * CacheUnusedCommand.
 *
 * To run Cache clear all command
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:cache:clear:unused')]
class CacheUnusedCommand extends Command
{
    /**
     * CacheUnusedCommand constructor.
     */
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To clear all unused cache repositories.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $preservedDirs = ['prod', 'dev'];
        $cacheDirname = $this->projectDir.'\var\cache';
        $cacheDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cacheDirname);
        $finder = Finder::create();
        $filesystem = new Filesystem();
        $cacheDirs = [];

        foreach ($finder->in($cacheDirname) as $file) {
            $matches = explode('\\', $file->getPath());
            if ('cache' === end($matches) && is_dir($file->getPathname())) {
                if (!in_array($file->getFilename(), $preservedDirs)) {
                    $cacheDirs[] = [
                        'path' => $file->getPath(),
                        'pathname' => $file->getPathname(),
                        'filename' => $file->getFilename(),
                    ];
                }
            }
        }

        foreach ($cacheDirs as $cacheDir) {
            $dirMatches = explode('\\', $cacheDir['pathname']);
            $tmpDirname = $cacheDir['path'].'\\'.uniqid().'-'.end($dirMatches);
            $filesystem->rename($cacheDir['pathname'], $tmpDirname);
            $filesystem->remove($tmpDirname);
        }

        $message = 'All unused cache repositories successfully deleted.';
        $io->block($message, 'OK', 'fg=black;bg=green', ' ', true);

        return Command::SUCCESS;
    }
}
