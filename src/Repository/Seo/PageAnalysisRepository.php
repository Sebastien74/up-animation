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
     * Latest snapshot scalars per page (keyed by "code|locale") for a whole website.
     * Only lightweight fields are selected (the full JSON report is not loaded here).
     *
     * @return array<string, array{code: ?string, locale: ?string, score: ?int, kb: int, high: int, medium: int, low: int, httpStatus: ?int, date: ?\DateTimeInterface}>
     */
    public function findLatestPerPage(Website $website): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select(
                's.urlCode AS code',
                's.locale AS locale',
                's.score AS score',
                's.htmlKb AS kb',
                's.severityHigh AS high',
                's.severityMedium AS medium',
                's.severityLow AS low',
                's.httpStatus AS httpStatus',
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
            ->delete(PageAnalysis::class, 's')
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
            ->delete(PageAnalysis::class, 's')
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
            ->delete(PageAnalysis::class, 's')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}
