<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Website;
use App\Repository\Core\ScheduledCommandRepository;
use Cron\CronExpression;

/**
 * ScheduledCommandReportService.
 *
 * Builds a view-ready report of a website's scheduled commands for the
 * admin dashboard: status, last execution and next computed run date.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class ScheduledCommandReportService
{
    public function __construct(private ScheduledCommandRepository $repository)
    {
    }

    /**
     * @return array<int, array{name: ?string, command: string, cronExpression: string, status: string, locked: bool, lastExecution: ?\DateTimeInterface, nextRun: ?\DateTimeImmutable}>
     */
    public function getReport(Website $website): array
    {
        $report = [];

        foreach ($this->repository->findReportRows($website) as $row) {
            $report[] = [
                'name' => $row['name'],
                'command' => $row['command'],
                'cronExpression' => $row['cronExpression'],
                'status' => $this->resolveStatus($row),
                'locked' => (bool) $row['locked'],
                'lastExecution' => $row['lastExecution'],
                'nextRun' => $this->resolveNextRun($row),
            ];
        }

        return $report;
    }

    /**
     * @param array{active: bool, lastExecution: ?\DateTimeInterface, lastReturnCode: ?int} $row
     */
    private function resolveStatus(array $row): string
    {
        if (!$row['active']) {
            return 'disabled';
        }
        if (null !== $row['lastReturnCode'] && 0 !== $row['lastReturnCode']) {
            return 'failed';
        }
        if (null !== $row['lastExecution']) {
            return 'ok';
        }

        return 'pending';
    }

    /**
     * @param array{active: bool, cronExpression: string, lastExecution: ?\DateTimeInterface} $row
     */
    private function resolveNextRun(array $row): ?\DateTimeImmutable
    {
        if (!$row['active']) {
            return null;
        }

        try {
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $nextRun = (new CronExpression($row['cronExpression']))->getNextRunDate($now);

            return \DateTimeImmutable::createFromInterface($nextRun);
        } catch (\Throwable) {
            return null;
        }
    }
}
