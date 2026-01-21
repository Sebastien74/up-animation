<?php

declare(strict_types=1);

namespace App\Repository\Module\Portfolio;

use App\Entity\Core\Website;
use App\Entity\Module\Portfolio\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
     * Find Category by url & locale.
     *
     * @throws NonUniqueResultException
     */
    public function findByUrlAndLocale(string $url, Website $website, string $locale, bool $preview = false): ?Category
    {
        return $this->optimizedQueryBuilder($locale, $website, null, null, $preview)
            ->andWhere('u.code = :code')
            ->setParameter('code', $url)
            ->getQuery()
            ->getOneOrNullResult();
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
            ->andWhere('c.website = :website')
            ->andWhere('u.locale = :locale')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->addSelect('w')
            ->addSelect('u')
            ->addSelect('s');

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
