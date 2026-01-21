<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Entity;
use App\Model\Core\WebsiteModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * EntityRepository.
 *
 * @extends ServiceEntityRepository<Entity>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EntityRepository extends ServiceEntityRepository
{
    /**
     * EntityRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Entity::class);
    }

    /**
     * Find Entity optimized query.
     *
     * @throws NonUniqueResultException
     */
    public function optimizedQuery(string $classname, ?WebsiteModel $website = null): ?Entity
    {
        if (!$website) {
            return null;
        }

        return $this->createQueryBuilder('e')
            ->leftJoin('e.website', 'w')
            ->andWhere('e.className = :className')
            ->andWhere('e.website = :website')
            ->setParameter('className', $classname)
            ->setParameter('website', $website->entity)
            ->addSelect('w')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
