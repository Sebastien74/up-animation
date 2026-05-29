<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Content\Feed\InstagramTokenRefresher;
use App\Service\Core\CronSchedulerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * InstagramRefreshTokenCommand.
 *
 * Refreshes Instagram long-lived tokens before they expire so the feed sync
 * never stops for lack of a valid token. Schedulable through the built-in
 * scheduler (core_scheduled_command); a weekly run is enough since each
 * refresh resets the 60-day window.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:instagram:refresh-token',
    description: 'Refresh Instagram long-lived tokens before they expire'
)]
class InstagramRefreshTokenCommand extends Command
{
    public function __construct(
        private readonly InstagramTokenRefresher $instagramTokenRefresher,
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
            $stats = $this->instagramTokenRefresher->refresh((bool) $input->getOption('force'));
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
