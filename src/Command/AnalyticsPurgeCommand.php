<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * AnalyticsPurgeCommand.
 *
 * Deletes raw events older than the retention window in small batches
 * to keep transaction size and replication lag bounded.
 *
 * The hourly table is purged on a longer window (12 months) and daily
 * is kept indefinitely.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:analytics:purge', description: 'Purge raw events past retention and aged hourly buckets.')]
final class AnalyticsPurgeCommand extends Command
{
    private const string EVENT_TABLE = 'upa_analytics_event';
    private const string HOURLY_TABLE = 'upa_analytics_hourly';
    private const int DEFAULT_BATCH_SIZE = 10000;
    private const int DEFAULT_EVENT_RETENTION_DAYS = 30;
    private const int DEFAULT_HOURLY_RETENTION_DAYS = 365;

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('event-retention-days', null, InputOption::VALUE_REQUIRED, 'Days of raw events to keep.', self::DEFAULT_EVENT_RETENTION_DAYS)
            ->addOption('hourly-retention-days', null, InputOption::VALUE_REQUIRED, 'Days of hourly buckets to keep.', self::DEFAULT_HOURLY_RETENTION_DAYS)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Rows removed per delete batch.', self::DEFAULT_BATCH_SIZE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $eventDays = max(1, (int) $input->getOption('event-retention-days'));
        $hourlyDays = max(1, (int) $input->getOption('hourly-retention-days'));
        $batchSize = max(100, (int) $input->getOption('batch-size'));

        $eventCutoff = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-'.$eventDays.' days');
        $hourlyCutoff = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-'.$hourlyDays.' days');

        $eventsDeleted = $this->purgeBatched(self::EVENT_TABLE, 'occurredAt', $eventCutoff, $batchSize);
        $io->writeln(sprintf('Events:  %d rows deleted (cutoff %s).', $eventsDeleted, $eventCutoff->format('Y-m-d H:i')));

        $hourlyDeleted = $this->purgeBatched(self::HOURLY_TABLE, 'bucketAt', $hourlyCutoff, $batchSize);
        $io->writeln(sprintf('Hourly:  %d rows deleted (cutoff %s).', $hourlyDeleted, $hourlyCutoff->format('Y-m-d H:i')));

        return Command::SUCCESS;
    }

    private function purgeBatched(string $table, string $column, \DateTimeImmutable $cutoff, int $batchSize): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s < :cutoff LIMIT %d', $table, $column, $batchSize);
        $total = 0;

        do {
            $deleted = (int) $this->connection->executeStatement($sql, [
                'cutoff' => $cutoff->format('Y-m-d H:i:s'),
            ]);
            $total += $deleted;
        } while ($deleted === $batchSize);

        return $total;
    }
}
