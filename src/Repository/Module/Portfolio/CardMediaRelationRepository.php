<?php

declare(strict_types=1);

namespace App\Repository\Module\Portfolio;

use App\Entity\Module\Portfolio\CardMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CardMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<CardMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CardMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * CardMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CardMediaRelation::class);
    }
}
