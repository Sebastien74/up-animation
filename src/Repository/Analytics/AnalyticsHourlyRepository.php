<?php

declare(strict_types=1);

namespace App\Repository\Analytics;

use App\Entity\Analytics\AnalyticsHourly;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AnalyticsHourlyRepository.
 *
 * @extends ServiceEntityRepository<AnalyticsHourly>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AnalyticsHourlyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyticsHourly::class);
    }

    /**
     * 7 x 24 heatmap of pageviews indexed by ISO weekday (1..7) and hour (0..23).
     *
     * @return list<array{dow:int, hour:int, pageviews:int}>
     *
     * @throws DBALException
     */
    public function findHeatmap(int $websiteId, \DateTimeImmutable $from, \DateTimeImmutable $to, ?string $locale = null): array
    {
        $localeClause = null !== $locale && '' !== $locale ? ' AND locale = :locale' : '';

        $sql = 'SELECT WEEKDAY(bucketAt) + 1 AS dow, HOUR(bucketAt) AS hour, SUM(pageviews) AS pageviews '
            .'FROM upa_analytics_hourly '
            .'WHERE websiteId = :websiteId AND bucketAt >= :from AND bucketAt < :to'.$localeClause.' '
            .'GROUP BY dow, hour';

        $params = [
            'websiteId' => $websiteId,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ];
        if ('' !== $localeClause) {
            $params['locale'] = $locale;
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, $params);

        return array_map(static fn (array $row): array => [
            'dow' => (int) $row['dow'],
            'hour' => (int) $row['hour'],
            'pageviews' => (int) $row['pageviews'],
        ], $rows);
    }
}
