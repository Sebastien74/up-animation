<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LayoutRepository.
 *
 * @extends ServiceEntityRepository<Layout>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutRepository extends ServiceEntityRepository
{
    /**
     * LayoutRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Layout::class);
    }

    /**
     * Layout ids (excluding page-owned layouts) grouped by the referenced module entity id.
     *
     * Complements PageRepository::findPagesGroupedByActionFilter() to cover modules placed
     * in non-page templates (news, product, category, catalog...). Single query.
     *
     * @return array<int, array<int>>
     */
    public function findNonPageLayoutIdsGroupedByActionFilter(
        int $websiteId,
        string $locale,
        string $classname,
        array $filterIds
    ): array {
        if (!$filterIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('l')
            ->select('l.id AS layoutId', 'ai.actionFilter AS actionFilter')
            ->innerJoin('l.zones', 'z')
            ->innerJoin('z.cols', 'c')
            ->innerJoin('c.blocks', 'b')
            ->innerJoin('b.action', 'a')
            ->innerJoin('b.actionIntls', 'ai')
            ->leftJoin(Page::class, 'p', 'WITH', 'p.layout = l')
            ->andWhere('l.website = :website')
            ->andWhere('a.entity = :entity')
            ->andWhere('ai.actionFilter IN (:actionFilters)')
            ->andWhere('ai.locale = :locale')
            ->andWhere('p.id IS NULL')
            ->setParameter('website', $websiteId)
            ->setParameter('entity', $classname)
            ->setParameter('actionFilters', $filterIds)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['actionFilter']][(int) $row['layoutId']] = (int) $row['layoutId'];
        }

        return array_map(static fn (array $ids): array => array_values($ids), $result);
    }
}
