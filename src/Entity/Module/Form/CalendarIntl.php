<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseIntl;
use App\Repository\Module\Form\CalendarIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CalendarIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_calendar_intls')]
#[ORM\Entity(repositoryClass: CalendarIntlRepository::class)]
class CalendarIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Calendar::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Calendar $calendar = null;

    public function getCalendar(): ?Calendar
    {
        return $this->calendar;
    }

    public function setCalendar(?Calendar $calendar): static
    {
        $this->calendar = $calendar;

        return $this;
    }
}
