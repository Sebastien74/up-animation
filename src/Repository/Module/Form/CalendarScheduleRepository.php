<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\Calendar;
use App\Entity\Module\Form\CalendarAppointment;
use App\Entity\Module\Form\CalendarSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CalendarScheduleRepository.
 *
 * @extends ServiceEntityRepository<CalendarSchedule>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarScheduleRepository extends ServiceEntityRepository
{
    /**
     * CalendarScheduleRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CalendarSchedule::class);
    }

    /**
     * Find by slugs and Calendar.
     *
     * @return array<CalendarAppointment>
     */
    public function findBySlugsAndCalendar(Calendar $calendar, array $slugs = []): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.formcalendar', 'c')
            ->andWhere('s.formcalendar = :calendar')
            ->andWhere('s.slug IN (:slugs)')
            ->setParameter('calendar', $calendar)
            ->setParameter('slugs', array_values($slugs))
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }
}
