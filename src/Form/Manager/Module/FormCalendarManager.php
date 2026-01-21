<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Module\Form\Calendar;
use App\Entity\Module\Form\CalendarSchedule;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * FormCalendarManager.
 *
 * Manage admin FormCalendar form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FormCalendarManager::class, 'key' => 'module_form_calendar_form_manager'],
])]
class FormCalendarManager
{
    /**
     * FormCalendarManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Set default schedules.
     */
    public function setSchedules(Calendar $calendar): void
    {
        if (0 === $calendar->getSchedules()->count()) {
            $days = [
                'monday' => $this->coreLocator->translator()->trans('Lundi', [], 'admin'),
                'tuesday' => $this->coreLocator->translator()->trans('Mardi', [], 'admin'),
                'wednesday' => $this->coreLocator->translator()->trans('Mercredi', [], 'admin'),
                'thursday' => $this->coreLocator->translator()->trans('Jeudi', [], 'admin'),
                'friday' => $this->coreLocator->translator()->trans('Vendredi', [], 'admin'),
                'saturday' => $this->coreLocator->translator()->trans('Samedi', [], 'admin'),
                'sunday' => $this->coreLocator->translator()->trans('Dimanche', [], 'admin'),
            ];

            $position = 1;
            foreach ($days as $slug => $adminName) {
                $schedule = new CalendarSchedule();
                $schedule->setAdminName($adminName);
                $schedule->setSlug($slug);
                $schedule->setPosition($position);
                $calendar->addSchedule($schedule);
                ++$position;
            }

            $this->coreLocator->em()->persist($schedule);
            $this->coreLocator->em()->flush();
        }
    }
}
