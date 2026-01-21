<?php

declare(strict_types=1);

namespace App\Repository\Module\Search;

use App\Entity\Core\Website;
use App\Entity\Module\Search\Search;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SearchRepository.
 *
 * @extends ServiceEntityRepository<Search>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SearchRepository extends ServiceEntityRepository
{
    /**
     * SearchRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Search::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Search
    {
        $statement = $this->createQueryBuilder('s')
            ->leftJoin('s.website', 'w')
            ->leftJoin('s.resultsPage', 'p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('s.website = :website')
            ->setParameter('website', $website)
            ->addSelect('w', 'p', 'u');

        if (is_numeric($filter)) {
            $statement->andWhere('s.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('s.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }
}
