<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Content\Feed\TikTokTokenRefresher;
use App\Service\Core\CronSchedulerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * TikTokRefreshTokenCommand.
 *
 * Refreshes TikTok tokens before the 24 h access token lapses so the feed sync
 * never stops. Schedulable through the built-in scheduler (core_scheduled_command);
 * given the 24 h access token, schedule it at least every few hours.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:tiktok:refresh-token',
    description: 'Refresh TikTok access tokens before they expire'
)]
class TikTokRefreshTokenCommand extends Command
{
    public function __construct(
        private readonly TikTokTokenRefresher $tiktokTokenRefresher,
        private readonly CronSchedulerService $cronSchedulerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Refresh every token regardless of its expiry date')
            ->addArgument('cronLogger', InputArgument::OPTIONAL, 'Cron scheduler Logger')
            ->addArgument('commandLogger', InputArgument::OPTIONAL, 'Command Logger');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $command = (string) $input->getArgument('command');

        try {
            $stats = $this->tiktokTokenRefresher->refresh((bool) $input->getOption('force'));
        } catch (\Throwable $exception) {
            $this->cronSchedulerService->logger($command.' '.$exception->getMessage(), null, false);
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $message = sprintf(
            '%s: %d refreshed, %d skipped, %d failed.',
            $command,
            $stats['refreshed'],
            $stats['skipped'],
            $stats['failed']
        );

        $this->cronSchedulerService->logger($message, null, $stats['failed'] === 0);

        if ($stats['failed'] > 0) {
            $io->warning($message.' A manual OAuth re-connection may be required for the failed accounts.');
        } else {
            $io->success($message);
        }

        return Command::SUCCESS;
    }
}
