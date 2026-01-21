<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseEntity;
use App\Repository\Module\Form\CalendarScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CalendarSchedule.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_calendar_schedule')]
#[ORM\Entity(repositoryClass: CalendarScheduleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CalendarSchedule extends BaseEntity
{
    #[ORM\OneToMany(targetEntity: CalendarTimeRange::class, mappedBy: 'schedule', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['startHour' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $timeRanges;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'schedules')]
    private ?Calendar $formcalendar = null;

    /**
     * CalendarSchedule constructor.
     */
    public function __construct()
    {
        $this->timeRanges = new ArrayCollection();
    }

    /**
     * @return Collection<int, CalendarTimeRange>
     */
    public function getTimeRanges(): Collection
    {
        return $this->timeRanges;
    }

    public function addTimeRange(CalendarTimeRange $timeRange): static
    {
        if (!$this->timeRanges->contains($timeRange)) {
            $this->timeRanges->add($timeRange);
            $timeRange->setSchedule($this);
        }

        return $this;
    }

    public function removeTimeRange(CalendarTimeRange $timeRange): static
    {
        if ($this->timeRanges->removeElement($timeRange)) {
            // set the owning side to null (unless already changed)
            if ($timeRange->getSchedule() === $this) {
                $timeRange->setSchedule(null);
            }
        }

        return $this;
    }

    public function getFormcalendar(): ?Calendar
    {
        return $this->formcalendar;
    }

    public function setFormcalendar(?Calendar $formcalendar): static
    {
        $this->formcalendar = $formcalendar;

        return $this;
    }
}
