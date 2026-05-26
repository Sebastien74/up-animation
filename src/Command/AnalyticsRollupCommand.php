<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Analytics\AnalyticsRollupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * AnalyticsRollupCommand.
 *
 * Builds analytics_hourly and analytics_daily aggregates from raw events.
 * Idempotent: re-running the same range re-computes the same buckets via
 * INSERT ... ON DUPLICATE KEY UPDATE. Safe to schedule nightly.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:analytics:rollup', description: 'Aggregate analytics events into hourly and daily buckets.')]
final class AnalyticsRollupCommand extends Command
{
    public function __construct(private readonly AnalyticsRollupService $rollupService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('hours', null, InputOption::VALUE_REQUIRED, 'How many past hours to (re)compute.', 48)
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'How many past days to (re)compute for the daily table.', 3);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->rollupService->run(
            (int) $input->getOption('hours'),
            (int) $input->getOption('days')
        );

        $io->writeln(sprintf('Hourly: %d bucket rows upserted.', $result['hourly']));
        $io->writeln(sprintf('Daily:  %d bucket rows upserted.', $result['daily']));

        return Command::SUCCESS;
    }
}
