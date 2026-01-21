<?php

declare(strict_types=1);

namespace App\Repository\Module\Table;

use App\Entity\Module\Table\Cell;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CellRepository.
 *
 * @extends ServiceEntityRepository<Cell>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CellRepository extends ServiceEntityRepository
{
    /**
     * CellRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Cell::class);
    }
}
