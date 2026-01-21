<?php

declare(strict_types=1);

namespace App\Repository\Module\Agenda;

use App\Entity\Module\Agenda\AgendaMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AgendaMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<AgendaMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AgendaMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * AgendaMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, AgendaMediaRelation::class);
    }
}
