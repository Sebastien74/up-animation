<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\PageAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageAnalysisRepository.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 * @extends ServiceEntityRepository<PageAnalysis>
 */
class PageAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PageAnalysis::class);
    }

    /**
     * Latest snapshots for a given page (website + url code + locale), newest first.
     *
     * @return array<int, PageAnalysis>
     */
    public function findLatestSnapshots(Website $website, ?string $code, ?string $locale, int $limit = 12): array
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
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Latest snapshot per page (keyed by "code|locale") for a whole website.
     *
     * @return array<string, PageAnalysis>
     */
    public function findLatestPerPage(Website $website): array
    {
        $snapshots = $this->createQueryBuilder('s')
            ->andWhere('s.website = :website')
            ->orderBy('s.createdAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setParameter('website', $website)
            ->getQuery()
            ->getResult();

        $latest = [];
        foreach ($snapshots as $snapshot) {
            $key = ((string) $snapshot->getUrlCode()).'|'.((string) $snapshot->getLocale());
            if (!isset($latest[$key])) {
                $latest[$key] = $snapshot;
            }
        }

        return $latest;
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
            ->delete(PageAnalysis::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}
