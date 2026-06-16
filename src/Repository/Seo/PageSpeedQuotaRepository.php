<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Seo\PageSpeedQuota;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageSpeedQuotaRepository.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 * @extends ServiceEntityRepository<PageSpeedQuota>
 */
class PageSpeedQuotaRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PageSpeedQuota::class);
    }

    /**
     * Number of API requests already consumed today. Fails open (returns 0) if the table
     * is not migrated yet, so the analysis dashboard never breaks on a fresh deploy.
     */
    public function usedToday(): int
    {
        try {
            $row = $this->findOneBy(['day' => $this->today()]);

            return $row ? $row->getCount() : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Add $count consumed requests to today's counter (creating the row if needed).
     */
    public function consume(int $count): void
    {
        if ($count <= 0) {
            return;
        }

        try {
            $today = $this->today();
            $row = $this->findOneBy(['day' => $today]);
            if (!$row instanceof PageSpeedQuota) {
                $row = (new PageSpeedQuota())->setDay($today);
                $this->getEntityManager()->persist($row);
            }

            $row->setCount($row->getCount() + $count);
            $this->getEntityManager()->flush();
        } catch (\Throwable) {
            // Table not migrated yet or transient DB error: skip silently (Google's own
            // quota still enforces the hard limit).
        }
    }

    private function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris'));
    }
}
