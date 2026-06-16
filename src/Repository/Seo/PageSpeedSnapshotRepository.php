<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\PageSpeedSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageSpeedSnapshotRepository.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 * @extends ServiceEntityRepository<PageSpeedSnapshot>
 */
class PageSpeedSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PageSpeedSnapshot::class);
    }

    /**
     * Latest snapshot for a given page (website + url code + locale), newest first.
     */
    public function findLatestForPage(Website $website, ?string $code, ?string $locale): ?PageSpeedSnapshot
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.website = :website')
            ->andWhere('s.urlCode = :code')
            ->andWhere('s.locale = :locale')
            ->orderBy('s.createdAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setParameter('website', $website)
            ->setParameter('code', (string) $code)
            ->setParameter('locale', (string) $locale)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Latest snapshot scalars per page (keyed by "code|locale") for a whole website.
     * Only lightweight fields are selected (the full JSON report is not loaded here).
     *
     * @return array<string, array{code: ?string, locale: ?string, perfMobile: ?int, perfDesktop: ?int, lcpMs: ?int, clsX1000: ?int, fieldData: bool, date: ?\DateTimeInterface}>
     */
    public function findLatestPerPage(Website $website): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select(
                's.urlCode AS code',
                's.locale AS locale',
                's.perfMobile AS perfMobile',
                's.perfDesktop AS perfDesktop',
                's.lcpMs AS lcpMs',
                's.clsX1000 AS clsX1000',
                's.fieldData AS fieldData',
                's.createdAt AS date'
            )
            ->andWhere('s.website = :website')
            ->orderBy('s.createdAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setParameter('website', $website)
            ->getQuery()
            ->getArrayResult();

        $latest = [];
        foreach ($rows as $row) {
            $key = ((string) $row['code']).'|'.((string) $row['locale']);
            if (!isset($latest[$key])) {
                $latest[$key] = $row;
            }
        }

        return $latest;
    }

    /**
     * Delete every snapshot of a website. Returns the number of rows removed.
     */
    public function deleteAllForWebsite(Website $website): int
    {
        return (int) $this->createQueryBuilder('s')
            ->delete(PageSpeedSnapshot::class, 's')
            ->where('s.website = :website')
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }

    /**
     * Delete every snapshot of a single page (website + url code + locale).
     * Returns the number of rows removed.
     */
    public function deleteForPage(Website $website, ?string $code, ?string $locale): int
    {
        return (int) $this->createQueryBuilder('s')
            ->delete(PageSpeedSnapshot::class, 's')
            ->where('s.website = :website')
            ->andWhere('s.urlCode = :code')
            ->andWhere('s.locale = :locale')
            ->setParameter('website', $website)
            ->setParameter('code', (string) $code)
            ->setParameter('locale', (string) $locale)
            ->getQuery()
            ->execute();
    }

    /**
     * Keep only the most recent $keep snapshots for a page, delete the older ones.
     */
    public function pruneOldSnapshots(Website $website, ?string $code, ?string $locale, int $keep = 20): void
    {
        $ids = $this->createQueryBuilder('s')
            ->select('s.id')
            ->andWhere('s.website = :website')
            ->andWhere('s.urlCode = :code')
            ->andWhere('s.locale = :locale')
            ->orderBy('s.createdAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setFirstResult($keep)
            ->setMaxResults(1000)
            ->setParameter('website', $website)
            ->setParameter('code', (string) $code)
            ->setParameter('locale', (string) $locale)
            ->getQuery()
            ->getSingleColumnResult();

        if (empty($ids)) {
            return;
        }

        $this->getEntityManager()->createQueryBuilder()
            ->delete(PageSpeedSnapshot::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}
