<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseEntity;
use App\Repository\Information\ScheduleDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * ScheduleDay.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information_schedule_day')]
#[ORM\Entity(repositoryClass: ScheduleDayRepository::class)]
class ScheduleDay extends BaseEntity
{
    #[ORM\Column(type: Types::STRING, length: 30)]
    private ?string $dayName = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $close = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $full = false;

    #[ORM\OneToMany(targetEntity: ScheduleOccurrence::class, mappedBy: 'day', cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $occurrences;

    #[ORM\ManyToOne(targetEntity: Information::class, cascade: ['persist'], inversedBy: 'scheduleDays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Information $information = null;

    /**
     * ScheduleDay constructor.
     */
    public function __construct()
    {
        $this->occurrences = new ArrayCollection();
    }

    public function getDayName(): ?string
    {
        return $this->dayName;
    }

    public function setDayName(string $dayName): static
    {
        $this->dayName = $dayName;

        return $this;
    }

    public function isClose(): ?bool
    {
        return $this->close;
    }

    public function setClose(bool $close): static
    {
        $this->close = $close;

        return $this;
    }

    public function isFull(): ?bool
    {
        return $this->full;
    }

    public function setFull(bool $full): static
    {
        $this->full = $full;

        return $this;
    }

    /**
     * @return Collection<int, ScheduleOccurrence>
     */
    public function getOccurrences(): Collection
    {
        return $this->occurrences;
    }

    public function addOccurrence(ScheduleOccurrence $occurrence): static
    {
        if (!$this->occurrences->contains($occurrence)) {
            $this->occurrences->add($occurrence);
            $occurrence->setDay($this);
        }

        return $this;
    }

    public function removeOccurrence(ScheduleOccurrence $occurrence): static
    {
        if ($this->occurrences->removeElement($occurrence)) {
            // set the owning side to null (unless already changed)
            if ($occurrence->getDay() === $this) {
                $occurrence->setDay(null);
            }
        }

        return $this;
    }

    public function getInformation(): ?Information
    {
        return $this->information;
    }

    public function setInformation(?Information $information): static
    {
        $this->information = $information;

        return $this;
    }
}
