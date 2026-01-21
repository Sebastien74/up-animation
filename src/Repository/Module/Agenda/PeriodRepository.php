<?php

declare(strict_types=1);

namespace App\Repository\Module\Agenda;

use App\Entity\Module\Agenda\Period;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PeriodRepository.
 *
 * @extends ServiceEntityRepository<Period>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PeriodRepository extends ServiceEntityRepository
{
    /**
     * PeriodRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Period::class);
    }
}
