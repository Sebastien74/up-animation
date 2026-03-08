<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseEntity;
use App\Repository\Module\Form\CalendarExceptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * CalendarException.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_calendar_exception')]
#[ORM\Entity(repositoryClass: CalendarExceptionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CalendarException extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'calendar';
    protected static array $interface = [
        'name' => 'formcalendarexception',
    ];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isClose = false;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'exceptions')]
    private ?Calendar $formcalendar = null;

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function isClose(): ?bool
    {
        return $this->isClose;
    }

    public function setClose(bool $isClose): static
    {
        $this->isClose = $isClose;

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
