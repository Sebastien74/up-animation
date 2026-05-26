<?php

declare(strict_types=1);

namespace App\Doctrine\Middleware;

/**
 * QueryProfiler.
 *
 * Per-request DBAL query counter shared between the QueryProfilerMiddleware
 * (records each executed query) and the SlowRequestSubscriber (reads totals
 * to expose them in the Server-Timing response header).
 *
 * Lives the time of one HTTP request: Symfony recreates the service container
 * for each request in PHP-FPM, so no manual reset is needed. The reset() method
 * exists for long-running worker contexts (FrankenPHP / Roadrunner).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class QueryProfiler
{
    private int $count = 0;
    private float $timeMs = 0.0;

    public function record(float $durationMs): void
    {
        ++$this->count;
        $this->timeMs += $durationMs;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTimeMs(): float
    {
        return $this->timeMs;
    }

    public function reset(): void
    {
        $this->count = 0;
        $this->timeMs = 0.0;
    }
}
