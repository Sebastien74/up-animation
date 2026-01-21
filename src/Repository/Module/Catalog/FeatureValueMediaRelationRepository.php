<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\FeatureValueMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CatalogRepository.
 *
 * @extends ServiceEntityRepository<FeatureValueMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureValueMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * VideoRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FeatureValueMediaRelation::class);
    }
}
