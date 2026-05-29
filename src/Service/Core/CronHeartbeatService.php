<?php

declare(strict_types=1);

namespace App\Service\Core;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * CronHeartbeatService.
 *
 * Traffic-driven scheduler trigger for shared hosting without a daemon.
 * Called from kernel.terminate once the HTTP response has been sent, so
 * the visitor sees no slowdown. A cache lock throttles the engine to at
 * most one run per minute regardless of traffic volume.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class CronHeartbeatService
{
    public const int THROTTLE_TTL = 60;
    private const string CACHE_KEY = 'scheduler_heartbeat_lock';

    public function __construct(
        private readonly CronSchedulerService $cronSchedulerService,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $schedulerLogger,
        private readonly bool $enabled = true,
    ) {
    }

    /**
     * Run the scheduler unless disabled or the 60 s throttle lock is still
     * held. The lock is acquired before execution, so concurrent requests in
     * the same window do not trigger duplicate runs.
     */
    public function runIfDue(): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $item = $this->cache->getItem(self::CACHE_KEY);
        } catch (InvalidArgumentException) {
            return;
        }

        if ($item->isHit()) {
            return;
        }

        $item->set(time());
        $item->expiresAfter(self::THROTTLE_TTL);
        $this->cache->save($item);

        try {
            $this->cronSchedulerService->execute();
        } catch (Throwable $exception) {
            $this->schedulerLogger->error('Scheduler heartbeat failed', ['exception' => $exception]);
        }
    }
}
