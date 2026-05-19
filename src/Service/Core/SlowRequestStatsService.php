<?php

declare(strict_types=1);

namespace App\Service\Core;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Finder\Finder;

/**
 * SlowRequestStatsService.
 *
 * Aggregates slow-request log entries (written by SlowRequestSubscriber)
 * into compact statistics for the admin dashboard. Result is cached so the
 * dashboard does not re-parse log files on every load.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class SlowRequestStatsService
{
    private const string CACHE_KEY = 'slow_requests_stats_v4';
    private const int CACHE_TTL = 300;
    private const int MAX_ENTRIES = 5000;
    private const int RECENT_WINDOW_HOURS = 24;
    private const int TOP_ROUTES_LIMIT = 5;

    /** Routes already filtered at write-time; kept here for retro-active cleanup of older entries. */
    private const array EXCLUDED_ROUTES = [
        '_wdt' => true,
        '_profiler' => true,
        '_profiler_home' => true,
        '_profiler_search' => true,
        '_profiler_search_bar' => true,
        '_profiler_search_results' => true,
        '_profiler_router' => true,
        '_profiler_exception' => true,
        '_profiler_exception_css' => true,
        '_fragment' => true,
        'front_activity' => true,
    ];

    /** @var array<int, array{0:int,1:int,2:string}> */
    private const array DURATION_BUCKETS = [
        [0, 1000, '< 1 s'],
        [1000, 2000, '1 – 2 s'],
        [2000, 5000, '2 – 5 s'],
        [5000, 10000, '5 – 10 s'],
        [10000, PHP_INT_MAX, '> 10 s'],
    ];

    public function __construct(
        private string $logDir,
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getStats(): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            return $item->get();
        }

        $stats = $this->compute();

        $item->set($stats);
        $item->expiresAfter(self::CACHE_TTL);
        $this->cache->save($item);

        return $stats;
    }

    /**
     * Return detailed entries (most-recent first) for the inspector page.
     *
     * @return list<array{timestamp:int,formatted_at:string,duration_ms:int,peak_memory_mb:int,route:string,uri:string,area:string,status:int}>
     */
    public function getEntries(?string $area = null, int $limit = 200): array
    {
        $entries = $this->readEntries(unlimited: true);

        if (null !== $area) {
            $entries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => $entry['area'] === $area
            ));
        }

        usort($entries, static fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        $entries = array_slice($entries, 0, $limit);

        foreach ($entries as &$entry) {
            $entry['formatted_at'] = (new \DateTimeImmutable('@'.$entry['timestamp']))
                ->setTimezone(new \DateTimeZone('Europe/Paris'))
                ->format('Y-m-d H:i:s');
        }

        return $entries;
    }

    /**
     * Force a recomputation on next read.
     *
     * @throws InvalidArgumentException
     */
    public function invalidate(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    private function compute(): array
    {
        $entries = $this->readEntries();
        $windowStart = (new \DateTimeImmutable('-'.self::RECENT_WINDOW_HOURS.' hours'))->getTimestamp();

        $totals = ['all' => 0, 'front' => 0, 'admin' => 0];
        $recentTotals = ['all' => 0, 'front' => 0, 'admin' => 0];
        $sumDuration = ['front' => 0, 'admin' => 0];
        $maxDuration = ['front' => 0, 'admin' => 0];
        $sumMemory = ['front' => 0, 'admin' => 0];
        $maxMemory = ['front' => 0, 'admin' => 0];
        $routes = ['front' => [], 'admin' => []];
        $hourly = $this->emptyHourly();
        $buckets = $this->emptyBuckets();
        $latest = ['front' => null, 'admin' => null];
        $lastSeen = null;

        foreach ($entries as $entry) {
            $area = ('admin' === $entry['area']) ? 'admin' : 'front';
            $totals['all']++;
            $totals[$area]++;
            $duration = $entry['duration_ms'];
            $memory = $entry['peak_memory_mb'];
            $ts = $entry['timestamp'];

            if ($ts >= $windowStart) {
                $recentTotals['all']++;
                $recentTotals[$area]++;
                $sumDuration[$area] += $duration;
                $sumMemory[$area] += $memory;
                $maxDuration[$area] = max($maxDuration[$area], $duration);
                $maxMemory[$area] = max($maxMemory[$area], $memory);

                // Aggregate by URI (path) so the dashboard surfaces the actual URL
                // that is slow, not just its Symfony route name.
                $uriKey = $entry['uri'] ?: 'unknown';
                $routes[$area][$uriKey] = ($routes[$area][$uriKey] ?? 0) + 1;

                $this->incrementHourly($hourly, $ts, $area);
                $this->incrementBucket($buckets, $duration, $area);

                if (null === $latest[$area] || $ts > $latest[$area]['timestamp']) {
                    $latest[$area] = $entry;
                }
            }

            if (null === $lastSeen || $ts > $lastSeen) {
                $lastSeen = $ts;
            }
        }

        return [
            'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'last_seen_at' => $lastSeen ? (new \DateTimeImmutable('@'.$lastSeen))
                ->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y-m-d H:i:s') : null,
            'window_hours' => self::RECENT_WINDOW_HOURS,
            'totals' => $totals,
            'recent' => [
                'all' => $recentTotals['all'],
                'front' => [
                    'count' => $recentTotals['front'],
                    'avg_duration_ms' => $recentTotals['front'] > 0
                        ? (int) round($sumDuration['front'] / $recentTotals['front']) : 0,
                    'max_duration_ms' => $maxDuration['front'],
                    'avg_memory_mb' => $recentTotals['front'] > 0
                        ? (int) round($sumMemory['front'] / $recentTotals['front']) : 0,
                    'max_memory_mb' => $maxMemory['front'],
                ],
                'admin' => [
                    'count' => $recentTotals['admin'],
                    'avg_duration_ms' => $recentTotals['admin'] > 0
                        ? (int) round($sumDuration['admin'] / $recentTotals['admin']) : 0,
                    'max_duration_ms' => $maxDuration['admin'],
                    'avg_memory_mb' => $recentTotals['admin'] > 0
                        ? (int) round($sumMemory['admin'] / $recentTotals['admin']) : 0,
                    'max_memory_mb' => $maxMemory['admin'],
                ],
            ],
            'top_routes' => [
                'front' => $this->topRoutes($routes['front']),
                'admin' => $this->topRoutes($routes['admin']),
            ],
            'hourly' => $hourly,
            'buckets' => $buckets,
            'latest' => $latest,
        ];
    }

    /**
     * Read recent log entries. When $unlimited is true, all rotating files are scanned
     * (useful for the inspector page); otherwise only files modified within the recent
     * window are considered, with a hard cap on the number of parsed lines.
     *
     * @return list<array{timestamp:int,duration_ms:int,peak_memory_mb:int,route:string,uri:string,area:string,status:int}>
     */
    private function readEntries(bool $unlimited = false): array
    {
        if (!is_dir($this->logDir)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->in($this->logDir)
            ->name('slow-requests*.log')
            ->depth('== 0')
            ->sortByModifiedTime()
            ->reverseSorting();

        $entries = [];
        $remaining = $unlimited ? PHP_INT_MAX : self::MAX_ENTRIES;
        $cutoff = $unlimited
            ? 0
            : (new \DateTimeImmutable('-'.(self::RECENT_WINDOW_HOURS + 1).' hours'))->getTimestamp();

        foreach ($finder as $file) {
            if ($remaining <= 0) {
                break;
            }
            if ($file->getMTime() < $cutoff) {
                continue;
            }

            $handle = @fopen($file->getPathname(), 'rb');
            if (false === $handle) {
                continue;
            }

            try {
                while ($remaining > 0 && false !== ($line = fgets($handle))) {
                    $parsed = $this->parseLine($line);
                    if (null === $parsed) {
                        continue;
                    }
                    // Excluded routes captured before the write-time filter was added
                    // are still dropped at read time for defense in depth.
                    if (isset(self::EXCLUDED_ROUTES[$parsed['route']])) {
                        continue;
                    }
                    $entries[] = $parsed;
                    $remaining--;
                }
            } finally {
                fclose($handle);
            }
        }

        return $entries;
    }

    /**
     * Extract a structured entry from a single Monolog log line.
     */
    private function parseLine(string $line): ?array
    {
        if (false === ($jsonStart = strpos($line, 'Slow request detected'))) {
            return null;
        }
        $jsonStart = strpos($line, '{', $jsonStart);
        if (false === $jsonStart) {
            return null;
        }
        $depth = 0;
        $length = strlen($line);
        $jsonEnd = $jsonStart;
        for ($i = $jsonStart; $i < $length; $i++) {
            if ('{' === $line[$i]) {
                $depth++;
            } elseif ('}' === $line[$i]) {
                $depth--;
                if (0 === $depth) {
                    $jsonEnd = $i;
                    break;
                }
            }
        }
        if (0 !== $depth) {
            return null;
        }

        $payload = substr($line, $jsonStart, $jsonEnd - $jsonStart + 1);
        try {
            $context = json_decode($payload, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $timestamp = null;
        if (preg_match('/^\[([^\]]+)\]/', $line, $matches) === 1) {
            try {
                $timestamp = (new \DateTimeImmutable($matches[1]))->getTimestamp();
            } catch (\Exception) {
                $timestamp = null;
            }
        }
        if (null === $timestamp) {
            return null;
        }

        return [
            'timestamp' => $timestamp,
            'duration_ms' => (int) ($context['duration_ms'] ?? 0),
            'peak_memory_mb' => (int) ($context['peak_memory_mb'] ?? 0),
            'route' => (string) ($context['route'] ?? 'unknown'),
            'uri' => (string) ($context['uri'] ?? ''),
            'area' => 'admin' === ($context['area'] ?? '') ? 'admin' : 'front',
            'status' => (int) ($context['status'] ?? 0),
            'profiler_token' => isset($context['profiler_token']) && is_string($context['profiler_token'])
                ? $context['profiler_token']
                : null,
        ];
    }

    private function topRoutes(array $routes): array
    {
        arsort($routes);

        return array_slice($routes, 0, self::TOP_ROUTES_LIMIT, true);
    }

    private function emptyHourly(): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $buckets = [];
        for ($i = self::RECENT_WINDOW_HOURS - 1; $i >= 0; $i--) {
            $slot = $now->modify('-'.$i.' hour')->format('Y-m-d H:00');
            $buckets[$slot] = ['front' => 0, 'admin' => 0];
        }

        return $buckets;
    }

    private function incrementHourly(array &$hourly, int $timestamp, string $area): void
    {
        $slot = (new \DateTimeImmutable('@'.$timestamp))
            ->setTimezone(new \DateTimeZone('Europe/Paris'))
            ->format('Y-m-d H:00');
        if (isset($hourly[$slot])) {
            $hourly[$slot][$area]++;
        }
    }

    private function emptyBuckets(): array
    {
        $buckets = [];
        foreach (self::DURATION_BUCKETS as [$min, $max, $label]) {
            $buckets[$label] = ['front' => 0, 'admin' => 0];
        }

        return $buckets;
    }

    private function incrementBucket(array &$buckets, int $duration, string $area): void
    {
        foreach (self::DURATION_BUCKETS as [$min, $max, $label]) {
            if ($duration >= $min && $duration < $max) {
                $buckets[$label][$area]++;
                return;
            }
        }
    }
}
