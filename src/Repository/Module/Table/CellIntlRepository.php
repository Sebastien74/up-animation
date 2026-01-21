<?php

declare(strict_types=1);

namespace App\Repository\Module\Table;

use App\Entity\Module\Table\CellIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CellIntlRepository.
 *
 * @extends ServiceEntityRepository<CellIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CellIntlRepository extends ServiceEntityRepository
{
    /**
     * CellIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CellIntl::class);
    }
}
