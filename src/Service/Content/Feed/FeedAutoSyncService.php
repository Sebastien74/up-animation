<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * FeedAutoSyncService.
 *
 * Lazy synchronization: providers are scheduled at request time
 * (controller call), then actually synced in kernel.terminate so the
 * HTTP response is already sent to the browser when the API calls happen.
 *
 * A cache lock (TTL = 12 h) prevents a provider from being synced more
 * than twice per day, regardless of traffic volume.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class FeedAutoSyncService
{
    public const int LOCK_TTL = 12 * 3600;
    private const string CACHE_KEY_PREFIX = 'feed_sync_lock_';

    /** @var string[] */
    private array $scheduled = [];

    public function __construct(
        private readonly FeedSyncService $feedSyncService,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Schedule a provider for sync at kernel.terminate, unless its
     * 12 h lock is still held. Returns true if scheduled, false if skipped.
     *
     * The lock is acquired immediately to prevent concurrent requests
     * from queuing duplicate syncs.
     */
    public function scheduleIfStale(string $provider): bool
    {
        try {
            $item = $this->cache->getItem(self::CACHE_KEY_PREFIX . $provider);
        } catch (InvalidArgumentException) {
            return false;
        }

        if ($item->isHit()) {
            return false;
        }

        $item->set(time());
        $item->expiresAfter(self::LOCK_TTL);
        $this->cache->save($item);

        if (!in_array($provider, $this->scheduled, true)) {
            $this->scheduled[] = $provider;
        }

        return true;
    }

    /**
     * Run pending syncs. Called by the kernel.terminate subscriber
     * once the response has been sent to the client.
     */
    public function runScheduled(): void
    {
        if ($this->scheduled === []) {
            return;
        }

        foreach ($this->scheduled as $provider) {
            try {
                $this->feedSyncService->sync($provider);
            } catch (Throwable $e) {
                $this->logger->error('Feed auto-sync failed', [
                    'provider' => $provider,
                    'exception' => $e,
                ]);
            }
        }

        $this->scheduled = [];
    }

    /**
     * Force-clear the lock for a provider so the next page load triggers a sync.
     * Used by the dashboard "Synchroniser maintenant" button.
     */
    public function clearLock(string $provider): void
    {
        try {
            $this->cache->deleteItem(self::CACHE_KEY_PREFIX . $provider);
        } catch (InvalidArgumentException) {
            // ignore
        }
    }

    /**
     * Manually mark a provider as just synced - sets the 12 h lock without
     * scheduling anything. Called after a manual sync (dashboard button)
     * to prevent the next page load from re-triggering an auto-sync.
     */
    public function markSynced(string $provider): void
    {
        try {
            $item = $this->cache->getItem(self::CACHE_KEY_PREFIX . $provider);
        } catch (InvalidArgumentException) {
            return;
        }
        $item->set(time());
        $item->expiresAfter(self::LOCK_TTL);
        $this->cache->save($item);
    }
}
