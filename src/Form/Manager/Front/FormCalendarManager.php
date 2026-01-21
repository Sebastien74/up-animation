<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Core\Website;
use App\Entity\Module\Form\Calendar;
use App\Entity\Module\Form\CalendarAppointment;
use App\Entity\Module\Form\CalendarException;
use App\Entity\Module\Form\CalendarSchedule;
use App\Entity\Module\Form\ContactForm;
use App\Service\Core\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * FormCalendarManager.
 *
 * Manage front Form Calendar
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormCalendarManager
{
    private const int DAYS_NUMBER = 3;
    private const array UN_WORK_DAYS = ['sunday'];

    private ?Request $request;
    private Calendar $calendar;
    private array $disableSlots = [];

    /**
     * FormCalendarManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly MailerService $mailer,
        private readonly FormManager $formManager,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Set current Calendar.
     */
    public function setCalendar(Website $website, ?ContactForm $contact = null): mixed
    {
        $requestCalendar = $this->request->get('calendar');

        $this->calendar = $contact instanceof ContactForm ? $contact->getCalendar() : null;

        if (!$this->calendar && $requestCalendar) {
            $this->calendar = $this->entityManager->getRepository(Calendar::class)->find($requestCalendar);
        } elseif (!$this->calendar) {
            $this->calendar = $this->entityManager->getRepository(Calendar::class)->findFirstByWebsite($website);
        }

        if ($contact && !$contact->getCalendar()) {
            $contact->setCalendar($this->calendar);
            $this->entityManager->persist($contact);
            $this->entityManager->flush();
        }

        return $this->calendar;
    }

    /**
     * Get render dates.
     *
     * @throws Exception
     */
    public function getDates(?ContactForm $contact = null): object|bool
    {
        if (!$this->calendar instanceof Calendar) {
            return false;
        }

        $daysNumbers = $contact && $this->calendar->getDaysPerPage() ? $this->calendar->getDaysPerPage() : self::DAYS_NUMBER;
        $startRequest = $this->request->get('startDate');
        $start = $startRequest ? new \DateTime($startRequest) : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $currentDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $limitDates = $this->getLimitDates($currentDate, $start, $daysNumbers);

        $this->getDisableSlots($start, $daysNumbers);

        $dates[$start->format('Y-m-d')]['datetime'] = $start;
        $dates[$start->format('Y-m-d')]['occurrences'] = $this->getOccurrences($start, $limitDates->minDatetime, $limitDates->maxDatetime);
        for ($i = 1; $i <= ($daysNumbers - 1); ++$i) {
            $date = new \DateTime($start->format('Y-m-d').' +'.$i.' day');
            $dates[$date->format('Y-m-d')]['datetime'] = $date;
            $dates[$date->format('Y-m-d')]['occurrences'] = $this->getOccurrences($date, $limitDates->minDatetime, $limitDates->maxDatetime);
        }

        $previous = $start->format('Y-m-d') !== $currentDate->format('Y-m-d')
            ? new \DateTime($start->format('Y-m-d').' -'.$daysNumbers.' day') : null;
        $next = new \DateTime($start->format('Y-m-d').' +'.$daysNumbers.' day');

        return (object) [
            'dates' => $dates,
            'start' => $start,
            'previous' => $previous,
            'next' => $next,
        ];
    }

    /**
     * ContactForm Appointment registration.
     *
     * @throws Exception
     */
    public function register(FormInterface $formCalendar, ?ContactForm $contact = null): false|string
    {
        if ($contact && $formCalendar->isSubmitted() && $formCalendar->isValid()) {
            $slotDate = $formCalendar->getData()['slot_date'];
            $date = new \DateTime(urldecode($slotDate));
            $existing = $this->entityManager->getRepository(CalendarAppointment::class)->findOneBy([
                'appointmentDate' => $date,
                'formcalendar' => $this->calendar,
            ]);

            if (!$existing) {
                $appointment = new CalendarAppointment();
                $appointment->setContactForm($contact);
                $appointment->setAppointmentDate($date);
                $appointment->setFormcalendar($this->calendar);
                $appointment->setPosition($this->calendar->getAppointments()->count() + 1);

                $contact->setCalendar($this->calendar);
                $contact->setTokenExpired(true);

                $this->entityManager->persist($contact);
                $this->entityManager->persist($appointment);
                $this->entityManager->flush();

                $this->sendEmail($contact);

                return 'success';
            } else {
                return 'no-available';
            }
        }

        return false;
    }

    /**
     * Get limit dates.
     *
     * @throws Exception
     */
    private function getLimitDates(\DateTime $currentDate, \DateTime $startDate, int $daysNumbers): object
    {
        $minHours = $this->calendar->getMinHours();
        $maxHours = $this->calendar->getMaxHours();

        $start = new \DateTime($startDate->format('Y-m-d 00:00'));
        $end = new \DateTime($start->format('Y-m-d').' +'.$daysNumbers.' day');
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);
        foreach ($period as $datetime) {
            if ($maxHours && in_array($this->getDayCode($datetime->format('w')), self::UN_WORK_DAYS)) {
                $maxHours = $maxHours + 24;
            }
        }

        $minDatetime = $minHours
            ? new \DateTime($currentDate->format('Y-m-d H:i:s').' +'.$minHours.' hours')
            : null;
        $maxDatetime = $maxHours
            ? new \DateTime($currentDate->format('Y-m-d H:i:s').' +'.$maxHours.' hours')
            : null;

        return (object) [
            'minDatetime' => $minDatetime,
            'maxDatetime' => $maxDatetime,
        ];
    }

    /**
     * Get existing Appointment[].
     *
     * @throws Exception
     */
    private function getDisableSlots(\DateTime $startDate, int $daysNumbers)
    {
        $start = new \DateTime($startDate->format('Y-m-d 00:00'));
        $end = new \DateTime($start->format('Y-m-d').' +'.$daysNumbers.' day');
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);

        /** Get Appointments */
        $appointmentsDb = $this->entityManager->getRepository(CalendarAppointment::class)->findBetweenDatesAndCalendar($this->calendar);
        foreach ($appointmentsDb as $appointment) {
            $this->disableSlots[] = $appointment->getAppointmentDate()->format('Y-m-d H:i:s');
        }

        /** Get Schedules */
        $schedulesDays = [];
        foreach ($period as $datetime) {
            $schedulesDays[] = $this->getDayCode($datetime->format('w'));
        }
        $schedulesDb = $this->entityManager->getRepository(CalendarSchedule::class)->findBySlugsAndCalendar($this->calendar, $schedulesDays);
        foreach ($schedulesDb as $schedule) {
            $this->getDisableSchedulesSlots($period, $schedule);
        }

        /* Get Exceptions */
        $this->getDisableExceptionsSlots();
    }

    /**
     * Get disabled schedules slots.
     *
     * @throws Exception
     */
    private function getDisableSchedulesSlots(\DatePeriod $period, CalendarSchedule $schedule)
    {
        $currentDate = null;
        foreach ($period as $datetime) {
            if ($this->getDayCode($datetime->format('w')) === $schedule->getSlug()) {
                $currentDate = $datetime;
                break;
            }
        }

        $openingTimes = [];
        foreach ($schedule->getTimeRanges() as $timeRange) {
            $start = $timeRange->getStartHour();
            $end = $timeRange->getEndHour();
            if ($start && $end) {
                $interval = \DateInterval::createFromDateString('1 minute');
                $period = new \DatePeriod($start, $interval, $end);
                foreach ($period as $datetime) {
                    $openingTimes[] = $currentDate->format('Y-m-d').' '.$datetime->format('H:i:s');
                }
            }
        }

        $start = new \DateTime($currentDate->format('Y-m-d 00:00'));
        $end = new \DateTime($currentDate->format('Y-m-d 23:59'));
        $interval = \DateInterval::createFromDateString('1 minute');
        $period = new \DatePeriod($start, $interval, $end);
        foreach ($period as $datetime) {
            if (!in_array($datetime->format('Y-m-d H:i:s'), $openingTimes) || empty($openingTimes)) {
                $this->disableSlots[] = $datetime->format('Y-m-d H:i:s');
            }
        }
    }

    /**
     * Get disabled Exceptions slots.
     *
     * @throws Exception
     */
    private function getDisableExceptionsSlots()
    {
        $exceptions = $this->entityManager->getRepository(CalendarException::class)->findBy(['formcalendar' => $this->calendar]);

        foreach ($exceptions as $exception) {
            $start = $end = null;
            $startDate = $exception->getStartDate();
            $endDate = $exception->getEndDate();

            /* Close day */
            if ($exception->getIsClose() && $startDate) {
                $start = new \DateTime($startDate->format('Y-m-d 00:00'));
                $end = $endDate ? new \DateTime($endDate->format('Y-m-d 18:00')) : $startDate->format('Y-m-d 23:59');
            } /* Schedules */
            elseif ($startDate && $endDate) {
                $start = new \DateTime($startDate->format('Y-m-d H:i'));
                $end = new \DateTime($endDate->format('Y-m-d H:i'));
            }

            if ($start && $end) {
                $interval = \DateInterval::createFromDateString('1 minutes');
                $period = new \DatePeriod($start, $interval, $end);
                foreach ($period as $datetime) {
                    $this->disableSlots[] = $datetime->format('Y-m-d H:i:s');
                }
            }
        }
    }

    /**
     * Get occurrences.
     *
     * @throws Exception
     */
    private function getOccurrences(\DateTime $dateTime, ?\DateTime $minDatetime = null, ?\DateTime $maxDatetime = null): array
    {
        $startHour = $this->calendar->getStartHour() instanceof \DateTime ? $this->calendar->getStartHour()->format('H:i') : '08:00';
        $start = new \DateTime($dateTime->format('Y-m-d '.$startHour));
        $endHour = $this->calendar->getEndHour() instanceof \DateTime ? $this->calendar->getEndHour()->format('H:i') : '20:00';
        $end = new \DateTime($dateTime->format('Y-m-d '.$endHour));
        $interval = \DateInterval::createFromDateString($this->calendar->getFrequency().' minutes');
        $period = new \DatePeriod($start, $interval, $end);

        $occurrences = [];
        foreach ($period as $datetime) {
            $status = !in_array($datetime->format('Y-m-d H:i:s'), $this->disableSlots) ? 'available' : 'unavailable';
            if ('available' == $status && $maxDatetime && $datetime > $maxDatetime) {
                $status = 'later';
            } elseif ('available' == $status && $minDatetime && $datetime < $minDatetime) {
                $status = 'unavailable';
            }
            $occurrences[] = [
                'datetime' => $datetime,
                'available' => $status,
            ];
        }

        return $occurrences;
    }

    /**
     * Get day code by key.
     */
    private function getDayCode($key): string
    {
        $days = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            0 => 'sunday',
        ];

        return $days[$key];
    }

    /**
     * Send email.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private function sendEmail(ContactForm $contact): void
    {
        $form = $contact->getForm();

        if ($form->getConfiguration()->isConfirmEmail()) {
            $website = $form->getWebsite();
            $frontTemplate = $website->getConfiguration()->getTemplate();
            $companyName = $this->formManager->getCompanyName($website);
            $calendarIntl = $this->formManager->getIntl($this->calendar);
            $formIntl = $this->formManager->getIntl($form);
            $subject = $calendarIntl->subject ? $calendarIntl->subject : ($formIntl->subject ? $formIntl->subject : $companyName);

            $this->mailer->setSubject($companyName.' - '.$subject);
            $this->mailer->setTo([$contact->getEmail()]);
            $this->mailer->setName($companyName);
            $this->mailer->setFrom($form->getConfiguration()->getSendingEmail());
            $this->mailer->setTemplate('front/'.$frontTemplate.'/actions/form/email/calendar.html.twig');
            $this->mailer->setArguments([
                'contact' => $contact,
                'calendar' => $contact->getCalendar(),
                'calendarIntl' => $calendarIntl,
                'formIntl' => $formIntl,
                'appointmentDate' => $contact->getAppointment()->getAppointmentDate(),
            ]);
            $this->mailer->send();
        }
    }
}
