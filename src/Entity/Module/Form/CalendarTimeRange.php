<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseEntity;
use App\Repository\Module\Form\CalendarTimeRangeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * CalendarTimeRange.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_calendar_time_range')]
#[ORM\Entity(repositoryClass: CalendarTimeRangeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CalendarTimeRange extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'schedule';
    protected static array $interface = [
        'name' => 'formcalendartimerange',
    ];

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $startHour = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $endHour = null;

    #[ORM\ManyToOne(targetEntity: CalendarSchedule::class, cascade: ['persist'], inversedBy: 'timeRanges')]
    private ?CalendarSchedule $schedule = null;

    public function getStartHour(): ?\DateTimeInterface
    {
        return $this->startHour;
    }

    public function setStartHour(?\DateTimeInterface $startHour): static
    {
        $this->startHour = $startHour;

        return $this;
    }

    public function getEndHour(): ?\DateTimeInterface
    {
        return $this->endHour;
    }

    public function setEndHour(?\DateTimeInterface $endHour): static
    {
        $this->endHour = $endHour;

        return $this;
    }

    public function getSchedule(): ?CalendarSchedule
    {
        return $this->schedule;
    }

    public function setSchedule(?CalendarSchedule $schedule): static
    {
        $this->schedule = $schedule;

        return $this;
    }
}
