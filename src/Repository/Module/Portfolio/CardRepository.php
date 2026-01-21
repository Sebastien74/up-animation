<?php

declare(strict_types=1);

namespace App\Repository\Module\Portfolio;

use App\Entity\Core\Website;
use App\Entity\Module\Portfolio\Card;
use App\Entity\Module\Portfolio\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CardRepository.
 *
 * @extends ServiceEntityRepository<Card>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CardRepository extends ServiceEntityRepository
{
    /**
     * CardRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Card::class);
    }

    /**
     * Find all published by Category order newest.
     *
     * @return array<Card>
     */
    public function findByListing(string $locale, Website $website, Listing $listing)
    {
        if ($listing->getCategories()->isEmpty()) {
            return [];
        }

        $orderBy = explode('-', $listing->getOrderBy());

        $qb = $this->optimizedQueryBuilder($locale, $website, $orderBy[0], strtoupper($orderBy[1]));

        $categoryIds = [];
        foreach ($listing->getCategories() as $key => $category) {
            $categoryIds[] = $category->getId();
        }
        if ($categoryIds) {
            $qb->andWhere('ca.id IN (:categoryIds)')
                ->setParameter('categoryIds', $categoryIds);
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * PublishedQueryBuilder.
     */
    public function optimizedQueryBuilder(
        string $locale,
        Website $website,
        string $sort = null,
        string $order = null,
        $preview = false,
        QueryBuilder $qb = null): QueryBuilder
    {
        $statement = $this->getOrCreateQueryBuilder($qb)
            ->leftJoin('c.website', 'w')
            ->leftJoin('c.urls', 'u')
            ->leftJoin('u.seo', 's')
            ->leftJoin('c.categories', 'ca')
            ->andWhere('c.website = :website')
            ->andWhere('u.locale = :locale')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->addSelect('w')
            ->addSelect('u')
            ->addSelect('s')
            ->addSelect('ca');

        if (!$preview) {
            $statement->andWhere('c.publicationStart IS NULL OR c.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('c.publicationEnd IS NULL OR c.publicationEnd > CURRENT_TIMESTAMP()')
                ->andWhere('c.publicationStart IS NOT NULL')
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
        return $qb ?: $this->createQueryBuilder('c');
    }
}
