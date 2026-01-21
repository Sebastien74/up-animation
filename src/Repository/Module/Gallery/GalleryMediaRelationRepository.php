<?php

declare(strict_types=1);

namespace App\Repository\Module\Gallery;

use App\Entity\Module\Gallery\GalleryMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GalleryMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<GalleryMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GalleryMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * GalleryMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, GalleryMediaRelation::class);
    }
}
