<?php

declare(strict_types=1);

namespace App\Repository\Module\Search;

use App\Entity\Core\Website;
use App\Entity\Module\Search\SearchValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SearchValueRepository.
 *
 * @extends ServiceEntityRepository<SearchValue>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SearchValueRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SearchValue::class);
    }

    public function save(SearchValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SearchValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * To get values order by less result.
     *
     * @return array<SearchValue>
     */
    public function findByWebsite(Website $website): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.search', 's')
            ->andWhere('s.website = :website')
            ->setParameter('website', $website)
            ->addOrderBy('v.resultCount', 'ASC')
            ->addOrderBy('v.counter', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
