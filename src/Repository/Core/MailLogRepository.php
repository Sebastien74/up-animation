<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\MailLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MailLogRepository.
 *
 * @extends ServiceEntityRepository<MailLog>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailLog::class);
    }

    /**
     * Index query builder used by paginator.
     */
    public function indexQueryBuilder(?string $status = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC');

        if (null !== $status) {
            $qb->andWhere('m.status = :status')
                ->setParameter('status', $status);
        }

        return $qb;
    }

    /**
     * Total emails persisted.
     */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Counts grouped by status (success | failed).
     *
     * @return array{success:int, failed:int}
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m.status AS status, COUNT(m.id) AS total')
            ->groupBy('m.status')
            ->getQuery()
            ->getArrayResult();

        $result = ['success' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $key = (string) $row['status'];
            if (isset($result[$key])) {
                $result[$key] = (int) $row['total'];
            }
        }

        return $result;
    }

    /**
     * Daily counts over the last $days days.
     *
     * @return array<string, array{success:int, failed:int, total:int}> Keyed by Y-m-d
     */
    public function countDaily(int $days = 30): array
    {
        $timezone = new \DateTimeZone('Europe/Paris');
        $since = (new \DateTimeImmutable('-'.($days - 1).' days', $timezone))->setTime(0, 0);

        // Pre-fill all buckets so the chart has no gaps.
        $buckets = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = (new \DateTimeImmutable('-'.$i.' days', $timezone))->format('Y-m-d');
            $buckets[$day] = ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        // Aggregate in PHP to stay portable across Doctrine DQL platforms.
        $rows = $this->createQueryBuilder('m')
            ->select('m.createdAt AS createdAt, m.status AS status')
            ->andWhere('m.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $createdAt = $row['createdAt'];
            if (!$createdAt instanceof \DateTimeInterface) {
                continue;
            }
            $day = $createdAt->format('Y-m-d');
            if (!isset($buckets[$day])) {
                continue;
            }
            $status = (string) $row['status'];
            if (isset($buckets[$day][$status])) {
                $buckets[$day][$status]++;
            }
            $buckets[$day]['total']++;
        }

        return $buckets;
    }

    /**
     * Number of emails sent in the last 24h.
     */
    public function countLast24h(): int
    {
        $since = new \DateTimeImmutable('-24 hours', new \DateTimeZone('Europe/Paris'));

        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}