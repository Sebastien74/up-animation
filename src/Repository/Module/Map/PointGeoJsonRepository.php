<?php

declare(strict_types=1);

namespace App\Repository\Module\Map;

use App\Entity\Module\Map\PointGeoJson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PointGeoJsonRepository.
 *
 * @extends ServiceEntityRepository<PointGeoJson>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PointGeoJsonRepository extends ServiceEntityRepository
{
    /**
     * PointGeoJsonRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PointGeoJson::class);
    }
}
