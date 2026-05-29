<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Content\Feed\FeedSyncService;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * FeedSyncCommand.
 *
 * Synchronizes social feeds (Instagram, TikTok) into the local DB
 * and downloads their medias under /public/feed/medias.
 *
 * Cron-able. Recommended frequency: every hour.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:feed:sync',
    description: 'Sync social feeds (Instagram, TikTok) to DB and /public/feed/medias'
)]
class FeedSyncCommand extends Command
{
    public function __construct(private readonly FeedSyncService $feedSyncService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'provider',
                null,
                InputOption::VALUE_REQUIRED,
                'Provider to sync (instagram|tiktok|all)',
                'all'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force re-download of already cached media files'
            )
            // Optional positional args injected by CronSchedulerService when run as a ScheduledCommand.
            ->addArgument('cronLogger', InputArgument::OPTIONAL, 'Cron scheduler Logger')
            ->addArgument('commandLogger', InputArgument::OPTIONAL, 'Command Logger');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $providerOption = (string) $input->getOption('provider');
        $providerName = $providerOption === 'all' ? null : $providerOption;
        $force = (bool) $input->getOption('force');

        try {
            $results = $this->feedSyncService->sync($providerName, $force);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($results === []) {
            $io->warning('No provider was synced.');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($results as $name => $stats) {
            $rows[] = [
                $name,
                $stats['added'],
                $stats['updated'],
                $stats['removed'],
                $stats['mediaDownloaded'],
            ];
        }

        $io->table(
            ['Provider', 'Added', 'Updated', 'Removed (soft)', 'Media downloaded'],
            $rows
        );
        $io->success('Feed sync done.');

        return Command::SUCCESS;
    }
}
