<?php

declare(strict_types=1);

namespace App\Repository\Module\Timeline;

use App\Entity\Core\Website;
use App\Entity\Module\Timeline\Timeline;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TimelineRepository.
 *
 * @extends ServiceEntityRepository<Timeline>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TimelineRepository extends ServiceEntityRepository
{
    /**
     * TimelineRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Timeline::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Timeline
    {
        $statement = $this->createQueryBuilder('t')
            ->leftJoin('t.website', 'w')
            ->leftJoin('t.steps', 's')
            ->leftJoin('s.intls', 'si')
            ->addSelect('w', 's', 'si');

        if (is_numeric($filter)) {
            $statement->andWhere('t.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('t.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }
}
