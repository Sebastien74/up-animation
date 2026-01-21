<?php

declare(strict_types=1);

namespace App\Repository\Module\Newscast;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Listing;
use App\Entity\Module\Newscast\Newscast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * NewscastRepository.
 *
 * @extends ServiceEntityRepository<Newscast>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastRepository extends ServiceEntityRepository
{
    /**
     * NewscastRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Newscast::class);
    }

    /**
     * Find Newscast by url & locale.
     *
     * @throws NonUniqueResultException
     */
    public function findByUrlAndLocale(string $url, Website $website, string $locale, bool $preview = false): ?Newscast
    {
        return $this->optimizedQueryBuilder($locale, $website, null, null, $preview)
            ->andWhere('u.code = :code')
            ->setParameter('code', $url)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all published order newest.
     *
     * @return array<Newscast>
     */
    public function findAllPublishedOrderByNewest(string $locale, Website $website, Newscast $excludeNewscast = null): array
    {
        $qb = $this->optimizedQueryBuilder($locale, $website);

        if ($excludeNewscast) {
            $qb->andWhere('n.id != :excludeId')
                ->setParameter('excludeId', $excludeNewscast->getId());
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Find all published by Category order newest.
     *
     * @return array<Newscast>
     */
    public function findByCategory(string $locale, Website $website, Category $category, Newscast $excludeNewscast = null, bool $preview = false): array
    {
        $orderBy = explode('-', $category->getOrderBy());
        $qb = $this->optimizedQueryBuilder($locale, $website, $orderBy[0], strtoupper($orderBy[1]), $preview)
            ->setMaxResults($category->getItemsPerPage())
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId());
        if ($excludeNewscast) {
            $qb->andWhere('n.id != :excludeId')
                ->setParameter('excludeId', $excludeNewscast->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all published by Category order newest.
     *
     * @return array<Newscast>
     */
    public function findByListing(string $locale, Website $website, Listing $listing, Newscast $excludeNewscast = null): array
    {
        $orderBy = explode('-', $listing->getOrderBy());

        $qb = $this->optimizedQueryBuilder($locale, $website, $orderBy[0], strtoupper($orderBy[1]));

        $categoryIds = [];
        foreach ($listing->getCategories() as $key => $category) {
            $categoryIds[] = $category->getId();
        }
        if ($categoryIds) {
            $qb->andWhere('n.category IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds);
        }

        if ($excludeNewscast) {
            $qb->andWhere('n.id != :excludeId')
                ->setParameter('excludeId', $excludeNewscast->getId());
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Find max result published order newest.
     *
     * @throws NonUniqueResultException
     */
    public function findMaxResultPublishedOrderByNewest(string $locale, Website $website, int $limit = 5): array|Newscast|null
    {
        $qb = $this->optimizedQueryBuilder($locale, $website)
            ->setMaxResults($limit)
            ->getQuery();

        if (1 === $limit) {
            return $qb->getOneOrNullResult();
        }

        return $qb->getResult();
    }

    /**
     * Find max result published order the newest by Category.
     *
     * @throws NonUniqueResultException
     */
    public function findMaxResultPublishedListingOrderByNewest(string $locale, Website $website, Listing $listing, int $limit = 5): array|Newscast|null
    {
        if ($listing->getCategories()->isEmpty()) {
            return null;
        }

        $orderBy = explode('-', $listing->getOrderBy());

        $qb = $this->optimizedQueryBuilder($locale, $website, $orderBy[0], strtoupper($orderBy[1]))
            ->setMaxResults($limit);

        $categoryIds = [];
        foreach ($listing->getCategories() as $key => $category) {
            $categoryIds[] = $category->getId();
        }
        if ($categoryIds) {
            $qb->andWhere('n.category IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds);
        }

        if (1 === $limit) {
            return $qb->getQuery()
                ->getOneOrNullResult();
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Find max result published order the newest by Category.
     *
     * @throws NonUniqueResultException
     */
    public function findMaxResultPublishedCategoryOrderByNewest(string $locale, Website $website, Category $category, int $limit = 5): array|Newscast|null
    {
        $orderBy = explode('-', $category->getOrderBy());
        $sort = !empty($orderBy[0]) ? $orderBy[0] : 'publicationStart';
        $order = !empty($orderBy[1]) ? strtoupper($orderBy[1]) : 'DESC';

        $statement = $this->optimizedQueryBuilder($locale, $website, $sort, $order)
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->setMaxResults($limit)
            ->getQuery();

        if (1 === $limit) {
            return $statement->getOneOrNullResult();
        }

        return $statement->getResult();
    }

    /**
     * Find for SEO.
     *
     * @throws NonUniqueResultException
     */
    public function findForSeo(string $locale, Website $website, string $urlCode): ?Newscast
    {
        if (!$urlCode) {
            return null;
        }

        return $this->createQueryBuilder('n')
            ->leftJoin('n.urls', 'u')
            ->andWhere('n.website = :website')
            ->andWhere('u.locale = :locale')
            ->andWhere('u.code = :code')
            ->setParameter('locale', $locale)
            ->setParameter('website', $website)
            ->setParameter('code', $urlCode)
            ->addSelect('u')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * optimizedQueryBuilder.
     */
    public function optimizedQueryBuilder(
        string $locale,
        Website $website,
        string $sort = null,
        string $order = null,
        bool $preview = false,
        QueryBuilder $qb = null): QueryBuilder
    {
        $sort = $sort ?: 'publicationStart';
        $order = $order ?: 'DESC';

        $statement = $this->getOrCreateQueryBuilder($qb)
            ->leftJoin('n.website', 'w')
            ->leftJoin('n.urls', 'u')
            ->leftJoin('u.seo', 's')
            ->leftJoin('n.category', 'c')
            ->andWhere('n.website = :website')
            ->andWhere('u.locale = :locale')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->addSelect('w')
            ->addSelect('u')
            ->addSelect('s')
            ->addSelect('c');

        if ('category' !== $sort) {
            $statement->orderBy('n.'.$sort, $order);
        }

        if (!$preview) {
            $statement->andWhere('n.publicationStart IS NULL OR n.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('n.publicationEnd IS NULL OR n.publicationEnd > CURRENT_TIMESTAMP()')
                ->andWhere('n.publicationStart IS NOT NULL')
                ->andWhere('u.online = :online')
                ->setParameter('online', true);
        }

        return $statement;
    }

    /**
     * Main QueryBuilder.
     */
    private function getOrCreateQueryBuilder(QueryBuilder $qb = null): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder('n');
    }
}
