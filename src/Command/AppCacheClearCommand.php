<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * AppCacheClearCommand.
 *
 * Optimized cache clear for production environments to avoid timeouts.
 */
#[AsCommand(name: 'app:cache:clear', description: 'Optimized cache clear using filesystem rename')]
class AppCacheClearCommand extends Command
{
    public function __construct(private readonly CacheCommand $cacheCommand)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting optimized cache clear...');

        try {
            $result = $this->cacheCommand->clear(true, false);
            $io->success($result);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error during cache clear: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
