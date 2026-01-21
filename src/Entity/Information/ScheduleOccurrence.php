<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseEntity;
use App\Repository\Information\ScheduleOccurrenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ScheduleOccurrence.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information_schedule_occurrence')]
#[ORM\Entity(repositoryClass: ScheduleOccurrenceRepository::class)]
class ScheduleOccurrence extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'day';
    protected static array $interface = [
        'name' => 'scheduleoccurrence',
    ];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startHour = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endHour = null;

    #[ORM\ManyToOne(targetEntity: ScheduleDay::class, cascade: ['persist'], inversedBy: 'occurrences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ScheduleDay $day = null;

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

    public function getDay(): ?ScheduleDay
    {
        return $this->day;
    }

    public function setDay(?ScheduleDay $day): static
    {
        $this->day = $day;

        return $this;
    }
}
