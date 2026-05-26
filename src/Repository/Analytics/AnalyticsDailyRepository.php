<?php

declare(strict_types=1);

namespace App\Repository\Analytics;

use App\Entity\Analytics\AnalyticsDaily;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AnalyticsDailyRepository.
 *
 * @extends ServiceEntityRepository<AnalyticsDaily>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AnalyticsDailyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyticsDaily::class);
    }

    /**
     * Daily series of visitors / sessions / pageviews.
     *
     * @return list<array{date:string, visitors:int, sessions:int, pageviews:int}>
     *
     * @throws DBALException
     */
    public function findSeries(int $websiteId, \DateTimeImmutable $from, \DateTimeImmutable $to, ?string $locale = null): array
    {
        [$localeClause, $params] = $this->localeClause($locale);

        $sql = 'SELECT bucketDate AS date, '
            .'SUM(visitors) AS visitors, SUM(sessions) AS sessions, SUM(pageviews) AS pageviews '
            .'FROM upa_analytics_daily '
            .'WHERE websiteId = :websiteId AND bucketDate >= :from AND bucketDate <= :to'.$localeClause.' '
            .'GROUP BY bucketDate ORDER BY bucketDate ASC';

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, array_merge([
            'websiteId' => $websiteId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], $params));

        return array_map(static fn (array $row): array => [
            'date' => (string) $row['date'],
            'visitors' => (int) $row['visitors'],
            'sessions' => (int) $row['sessions'],
            'pageviews' => (int) $row['pageviews'],
        ], $rows);
    }

    /**
     * @return list<array{label:string, value:int}>
     *
     * @throws DBALException
     */
    public function findBreakdown(int $websiteId, \DateTimeImmutable $from, \DateTimeImmutable $to, string $dimension, string $metric, int $limit = 10, ?string $locale = null): array
    {
        $allowedDimensions = ['urlPath', 'countryCode', 'device', 'locale'];
        $allowedMetrics = ['pageviews', 'sessions', 'visitors'];

        if (!in_array($dimension, $allowedDimensions, true) || !in_array($metric, $allowedMetrics, true)) {
            return [];
        }

        [$localeClause, $params] = $this->localeClause($locale);

        $sql = sprintf(
            'SELECT %1$s AS label, SUM(%2$s) AS value '
            .'FROM upa_analytics_daily '
            .'WHERE websiteId = :websiteId AND bucketDate >= :from AND bucketDate <= :to'.$localeClause.' '
            .'AND %1$s IS NOT NULL '
            .'GROUP BY %1$s ORDER BY value DESC LIMIT %3$d',
            $dimension,
            $metric,
            max(1, $limit)
        );

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, array_merge([
            'websiteId' => $websiteId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], $params));

        return array_map(static fn (array $row): array => [
            'label' => (string) $row['label'],
            'value' => (int) $row['value'],
        ], $rows);
    }

    /**
     * @return iterable<int, array{date:string, urlPath:string, countryCode:?string, device:?string, locale:?string, visitors:int, sessions:int, pageviews:int}>
     *
     * @throws DBALException
     */
    public function streamRows(int $websiteId, \DateTimeImmutable $from, \DateTimeImmutable $to, ?string $locale = null): iterable
    {
        [$localeClause, $params] = $this->localeClause($locale);

        $sql = 'SELECT bucketDate AS date, urlPath, countryCode, device, locale, visitors, sessions, pageviews '
            .'FROM upa_analytics_daily '
            .'WHERE websiteId = :websiteId AND bucketDate >= :from AND bucketDate <= :to'.$localeClause.' '
            .'ORDER BY bucketDate ASC, urlPath ASC';

        $result = $this->getEntityManager()->getConnection()->executeQuery($sql, array_merge([
            'websiteId' => $websiteId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], $params));

        while (false !== ($row = $result->fetchAssociative())) {
            yield [
                'date' => (string) $row['date'],
                'urlPath' => (string) $row['urlPath'],
                'countryCode' => null === $row['countryCode'] ? null : (string) $row['countryCode'],
                'device' => null === $row['device'] ? null : (string) $row['device'],
                'locale' => null === $row['locale'] ? null : (string) $row['locale'],
                'visitors' => (int) $row['visitors'],
                'sessions' => (int) $row['sessions'],
                'pageviews' => (int) $row['pageviews'],
            ];
        }
    }

    /**
     * @return array{visitors:int, sessions:int, pageviews:int}
     *
     * @throws DBALException
     */
    public function findTotals(int $websiteId, \DateTimeImmutable $from, \DateTimeImmutable $to, ?string $locale = null): array
    {
        [$localeClause, $params] = $this->localeClause($locale);

        $sql = 'SELECT SUM(visitors) AS visitors, SUM(sessions) AS sessions, SUM(pageviews) AS pageviews '
            .'FROM upa_analytics_daily '
            .'WHERE websiteId = :websiteId AND bucketDate >= :from AND bucketDate <= :to'.$localeClause;

        $row = $this->getEntityManager()->getConnection()->fetchAssociative($sql, array_merge([
            'websiteId' => $websiteId,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], $params)) ?: [];

        return [
            'visitors' => (int) ($row['visitors'] ?? 0),
            'sessions' => (int) ($row['sessions'] ?? 0),
            'pageviews' => (int) ($row['pageviews'] ?? 0),
        ];
    }

    /**
     * @return list<string>
     *
     * @throws DBALException
     */
    public function findAvailableLocales(int $websiteId): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT DISTINCT locale FROM upa_analytics_daily WHERE websiteId = :websiteId AND locale IS NOT NULL ORDER BY locale ASC',
            ['websiteId' => $websiteId]
        );

        return array_map(static fn (array $row): string => (string) $row['locale'], $rows);
    }

    /**
     * @return array{0:string, 1:array<string,string>}
     */
    private function localeClause(?string $locale): array
    {
        if (null === $locale || '' === $locale) {
            return ['', []];
        }

        return [' AND locale = :locale', ['locale' => $locale]];
    }
}
