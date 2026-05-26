<?php

declare(strict_types=1);

namespace App\Repository\Analytics;

use App\Entity\Analytics\AnalyticsEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AnalyticsEventRepository.
 *
 * @extends ServiceEntityRepository<AnalyticsEvent>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AnalyticsEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyticsEvent::class);
    }

    /**
     * Top referrer domains over the retention window.
     *
     * @return list<array{label:string, value:int}>
     *
     * @throws DBALException
     */
    /**
     * Top click targets grouped by the payload label.
     *
     * @return list<array{label:string, action:string, count:int}>
     *
     * @throws DBALException
     */
    public function findTopClicks(int $websiteId, \DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10, ?string $locale = null): array
    {
        $localeClause = null !== $locale && '' !== $locale ? ' AND locale = :locale' : '';

        $sql = 'SELECT '
            ."COALESCE(JSON_UNQUOTE(JSON_EXTRACT(eventPayload, '$.label')), JSON_UNQUOTE(JSON_EXTRACT(eventPayload, '$.text')), urlPath) AS label, "
            ."COALESCE(JSON_UNQUOTE(JSON_EXTRACT(eventPayload, '$.action')), 'click') AS action, "
            .'COUNT(*) AS count '
            .'FROM upa_analytics_event '
            ."WHERE websiteId = :websiteId AND eventType = 'click' AND occurredAt >= :from AND occurredAt < :to".$localeClause.' '
            .'GROUP BY label, action ORDER BY count DESC LIMIT '.max(1, $limit);

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
            'label' => (string) ($row['label'] ?? ''),
            'action' => (string) ($row['action'] ?? 'click'),
            'count' => (int) $row['count'],
        ], $rows);
    }

    public function findTopReferrers(int $websiteId, \DateTimeImmutable $from, \DateTimeImmutable $to, int $limit = 10, ?string $locale = null): array
    {
        $localeClause = null !== $locale && '' !== $locale ? ' AND locale = :locale' : '';

        $sql = 'SELECT referrerDomain AS label, COUNT(DISTINCT sessionHash) AS value '
            .'FROM upa_analytics_event '
            .'WHERE websiteId = :websiteId AND occurredAt >= :from AND occurredAt < :to'.$localeClause.' '
            .'AND referrerDomain IS NOT NULL '
            .'GROUP BY referrerDomain ORDER BY value DESC LIMIT '.max(1, $limit);

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
            'label' => (string) $row['label'],
            'value' => (int) $row['value'],
        ], $rows);
    }
}
