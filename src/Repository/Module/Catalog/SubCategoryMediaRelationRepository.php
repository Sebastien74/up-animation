<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\SubCategoryMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SubCategoryMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<SubCategoryMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SubCategoryMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * SubCategoryMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SubCategoryMediaRelation::class);
    }
}
