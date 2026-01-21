<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CategoryRepository.
 *
 * @extends ServiceEntityRepository<Category>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Category::class);
    }

    /**
     * Find all online by locale.
     *
     * @return array<Category>
     */
    public function findBySlug(Website $website, string $locale, string $slug): array
    {
        return $this->optimizedQueryBuilder($locale, $website, 'position', 'ASC', $slug)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all online by locale.
     *
     * @return array<Category>
     */
    public function findAllByLocale(Website $website, string $locale, string $sort = 'ASC', string $order = 'position'): array
    {
        return $this->optimizedQueryBuilder($locale, $website, $order, $sort)
            ->getQuery()
            ->getResult();
    }

    /**
     * Optimized QueryBuilder.
     */
    private function optimizedQueryBuilder(
        string $locale,
        Website $website,
        ?string $order = null,
        ?string $sort = null,
        ?string $slug = null,
        ?QueryBuilder $qb = null): QueryBuilder
    {
        $sort = $sort ?: 'DESC';
        $order = $order ?: 'position';

        $statement = $this->getOrCreateQueryBuilder($qb)
            ->leftJoin('c.website', 'w')
            ->leftJoin('c.intls', 'i')
            ->leftJoin('c.mediaRelations', 'mr')
            ->andWhere('c.website = :website')
            ->andWhere('i.locale = :locale')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->addSelect('w')
            ->addSelect('i')
            ->addSelect('mr');

        if ($slug) {
            $statement->andWhere('c.slug = :slug')
                ->setParameter('slug', $slug);
        }

        if ('title' === $order) {
            $statement->orderBy('i.'.$order, $sort);
        } else {
            $statement->orderBy('c.'.$order, $sort);
        }

        return $statement;
    }

    /**
     * Main QueryBuilder.
     */
    private function getOrCreateQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder('c');
    }

    /**
     * Save.
     */
    public function save(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
