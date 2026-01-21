<?php

declare(strict_types=1);

namespace App\Repository\Module\Timeline;

use App\Entity\Module\Timeline\StepMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * StepMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<StepMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StepMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * StepMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, StepMediaRelation::class);
    }
}
