<?php

declare(strict_types=1);

namespace App\Repository\Gdpr;

use App\Entity\Gdpr\GroupMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GroupMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<GroupMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * GroupMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, GroupMediaRelation::class);
    }
}
