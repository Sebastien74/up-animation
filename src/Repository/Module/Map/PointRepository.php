<?php

declare(strict_types=1);

namespace App\Repository\Module\Map;

use App\Entity\Module\Map\Point;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PointRepository.
 *
 * @extends ServiceEntityRepository<Point>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PointRepository extends ServiceEntityRepository
{
    /**
     * PointRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Point::class);
    }
}
