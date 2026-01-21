<?php

declare(strict_types=1);

namespace App\Repository\Module\Menu;

use App\Entity\Module\Menu\LinkMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LinkMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<LinkMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LinkMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * LinkRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, LinkMediaRelation::class);
    }
}
