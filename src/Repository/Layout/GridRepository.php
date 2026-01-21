<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\Grid;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GridRepository.
 *
 * @extends ServiceEntityRepository<Grid>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GridRepository extends ServiceEntityRepository
{
    /**
     * GridRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Grid::class);
    }
}
