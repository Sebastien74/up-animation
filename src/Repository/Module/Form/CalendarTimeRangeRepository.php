<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\CalendarTimeRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CalendarTimeRangeRepository.
 *
 * @extends ServiceEntityRepository<CalendarTimeRange>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarTimeRangeRepository extends ServiceEntityRepository
{
    /**
     * CalendarTimeRangeRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CalendarTimeRange::class);
    }
}
