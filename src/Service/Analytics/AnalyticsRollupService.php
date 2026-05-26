<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * AnalyticsRollupService.
 *
 * Builds analytics_hourly and analytics_daily aggregates from raw events.
 * Reused by the scheduled command and by the admin stats page (throttled
 * so a refresh storm cannot turn into a query storm).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class AnalyticsRollupService
{
    private const string EVENT_TABLE = 'upa_analytics_event';
    private const string HOURLY_TABLE = 'upa_analytics_hourly';
    private const string DAILY_TABLE = 'upa_analytics_daily';
    private const string THROTTLE_KEY = 'analytics.rollup.last_run';

    public function __construct(
        private Connection $connection,
        private CacheInterface $cache,
    ) {
    }

    /**
     * @return array{hourly:int, daily:int}
     *
     * @throws DBALException
     */
    public function run(int $hours = 48, int $days = 3): array
    {
        $hours = max(1, $hours);
        $days = max(1, $days);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $hourlyTo = $now->setTime((int) $now->format('H'), 0);
        $hourlyFrom = $hourlyTo->modify('-'.$hours.' hours');
        $dailyTo = $now->setTime(0, 0);
        $dailyFrom = $dailyTo->modify('-'.$days.' days');

        return [
            'hourly' => $this->rollupHourly($hourlyFrom, $hourlyTo),
            'daily' => $this->rollupDaily($dailyFrom, $dailyTo),
        ];
    }

    /**
     * Runs a rollup at most once per $minIntervalSeconds. Returns true if
     * a rollup ran, false if the cached cooldown skipped it.
     *
     * @throws InvalidArgumentException
     */
    public function runThrottled(int $minIntervalSeconds = 60, int $hours = 6, int $days = 2): bool
    {
        $ran = false;
        $this->cache->get(self::THROTTLE_KEY, function (ItemInterface $item) use (&$ran, $minIntervalSeconds, $hours, $days): int {
            $item->expiresAfter(max(15, $minIntervalSeconds));
            try {
                $this->run($hours, $days);
                $ran = true;
            } catch (DBALException) {
                // Stats are non-critical: a failed rollup must never break the admin view.
            }

            return time();
        });

        return $ran;
    }

    /**
     * @throws DBALException
     */
    private function rollupHourly(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $sql = 'INSERT INTO '.self::HOURLY_TABLE
            .' (websiteId, bucketAt, urlPath, countryCode, device, locale, visitors, sessions, pageviews, bounces, durationSum) '
            .'SELECT '
            .'  websiteId, '
            .'  DATE_FORMAT(occurredAt, \'%Y-%m-%d %H:00:00\') AS bucketAt, '
            .'  urlPath, '
            .'  countryCode, '
            .'  device, '
            .'  locale, '
            .'  COUNT(DISTINCT sessionHash) AS visitors, '
            .'  COUNT(DISTINCT sessionHash) AS sessions, '
            .'  SUM(CASE WHEN eventType = \'pageview\' THEN 1 ELSE 0 END) AS pageviews, '
            .'  0 AS bounces, '
            .'  0 AS durationSum '
            .'FROM '.self::EVENT_TABLE.' '
            .'WHERE occurredAt >= :from AND occurredAt < :to '
            .'GROUP BY websiteId, bucketAt, urlPath, countryCode, device, locale '
            .'ON DUPLICATE KEY UPDATE '
            .'  visitors = VALUES(visitors), '
            .'  sessions = VALUES(sessions), '
            .'  pageviews = VALUES(pageviews)';

        return (int) $this->connection->executeStatement($sql, [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @throws DBALException
     */
    private function rollupDaily(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $sql = 'INSERT INTO '.self::DAILY_TABLE
            .' (websiteId, bucketDate, urlPath, countryCode, device, locale, visitors, sessions, pageviews, bounces, durationSum) '
            .'SELECT '
            .'  websiteId, '
            .'  DATE(occurredAt) AS bucketDate, '
            .'  urlPath, '
            .'  countryCode, '
            .'  device, '
            .'  locale, '
            .'  COUNT(DISTINCT sessionHash) AS visitors, '
            .'  COUNT(DISTINCT sessionHash) AS sessions, '
            .'  SUM(CASE WHEN eventType = \'pageview\' THEN 1 ELSE 0 END) AS pageviews, '
            .'  0 AS bounces, '
            .'  0 AS durationSum '
            .'FROM '.self::EVENT_TABLE.' '
            .'WHERE occurredAt >= :from AND occurredAt < :to '
            .'GROUP BY websiteId, bucketDate, urlPath, countryCode, device, locale '
            .'ON DUPLICATE KEY UPDATE '
            .'  visitors = VALUES(visitors), '
            .'  sessions = VALUES(sessions), '
            .'  pageviews = VALUES(pageviews)';

        return (int) $this->connection->executeStatement($sql, [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ]);
    }
}
