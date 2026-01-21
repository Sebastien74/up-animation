<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\ZoneMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ZoneMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<ZoneMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZoneMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * ZoneMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ZoneMediaRelation::class);
    }
}
