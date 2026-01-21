<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\Calendar;
use App\Entity\Module\Form\CalendarAppointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CalendarAppointmentRepository.
 *
 * @extends ServiceEntityRepository<CalendarAppointment>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarAppointmentRepository extends ServiceEntityRepository
{
    /**
     * CalendarAppointmentRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CalendarAppointment::class);
    }

    /**
     * Find between dates and Calendar.
     *
     * @return array<CalendarAppointment>
     */
    public function findBetweenDatesAndCalendar(Calendar $calendar): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.formcalendar', 'c')
            ->andWhere('a.formcalendar = :calendar')
            ->setParameter('calendar', $calendar)
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }
}
