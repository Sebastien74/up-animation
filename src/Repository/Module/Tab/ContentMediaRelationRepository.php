<?php

declare(strict_types=1);

namespace App\Repository\Module\Tab;

use App\Entity\Module\Tab\ContentMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContentMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<ContentMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContentMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * ContentMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ContentMediaRelation::class);
    }
}
