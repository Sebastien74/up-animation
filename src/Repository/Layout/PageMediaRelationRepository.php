<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\PageMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<PageMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * PageMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PageMediaRelation::class);
    }
}
