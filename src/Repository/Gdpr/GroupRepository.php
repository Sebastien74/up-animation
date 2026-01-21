<?php

declare(strict_types=1);

namespace App\Repository\Gdpr;

use App\Entity\Gdpr\Group;
use App\Model\Core\ConfigurationModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GroupRepository.
 *
 * @extends ServiceEntityRepository<Group>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupRepository extends ServiceEntityRepository
{
    /**
     * GroupRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Group::class);
    }

    /**
     * Get by Category ConfigurationModel.
     *
     * @throws NonUniqueResultException
     */
    public function findByConfiguration(ConfigurationModel $configuration, string $slug): ?Group
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.gdprcategory', 'c')
            ->addSelect('c')
            ->andWhere('g.slug = :slug')
            ->setParameter(':slug', $slug)
            ->andWhere('c.configuration = :configuration')
            ->setParameter('configuration', $configuration->entity)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
