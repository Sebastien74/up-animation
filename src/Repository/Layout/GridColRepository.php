<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\GridCol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GridColRepository.
 *
 * @extends ServiceEntityRepository<GridCol>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GridColRepository extends ServiceEntityRepository
{
    /**
     * GridColRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, GridCol::class);
    }
}
