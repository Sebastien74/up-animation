<?php

declare(strict_types=1);

namespace App\Repository\Module\Map;

use App\Entity\Module\Map\PointMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PointMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<PointMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PointMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * PointMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PointMediaRelation::class);
    }
}
