<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseEntity;
use App\Repository\Module\Form\CalendarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Calendar.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_calendar')]
#[ORM\Entity(repositoryClass: CalendarRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Calendar extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'form';
    protected static array $interface = [
        'name' => 'formcalendar',
        'buttons' => [
            'appointments' => 'admin_formcalendarappointment_index',
        ],
    ];
    protected static array $labels = [
        'admin_formcalendarappointment_index' => 'Rendez-vous',
    ];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $receivingEmails = [];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $controls = true;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $daysPerPage = 3;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private int $frequency = 10;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $startHour = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $endHour = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $minHours = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $maxHours = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $reference = null;

    #[ORM\OneToMany(targetEntity: CalendarAppointment::class, mappedBy: 'formcalendar', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $appointments;

    #[ORM\OneToMany(targetEntity: CalendarSchedule::class, mappedBy: 'formcalendar', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $schedules;

    #[ORM\OneToMany(targetEntity: CalendarException::class, mappedBy: 'formcalendar', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $exceptions;

    #[ORM\OneToMany(targetEntity: CalendarIntl::class, mappedBy: 'calendar', cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'calendars')]
    private ?Form $form = null;

    /**
     * Calendar constructor.
     */
    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->schedules = new ArrayCollection();
        $this->exceptions = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function getReceivingEmails(): ?array
    {
        return $this->receivingEmails;
    }

    public function setReceivingEmails(?array $receivingEmails): static
    {
        $this->receivingEmails = $receivingEmails;

        return $this;
    }

    public function isControls(): ?bool
    {
        return $this->controls;
    }

    public function setControls(bool $controls): static
    {
        $this->controls = $controls;

        return $this;
    }

    public function getDaysPerPage(): ?int
    {
        return $this->daysPerPage;
    }

    public function setDaysPerPage(?int $daysPerPage): static
    {
        $this->daysPerPage = $daysPerPage;

        return $this;
    }

    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    public function setFrequency(?int $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

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

    public function getMinHours(): ?int
    {
        return $this->minHours;
    }

    public function setMinHours(?int $minHours): static
    {
        $this->minHours = $minHours;

        return $this;
    }

    public function getMaxHours(): ?int
    {
        return $this->maxHours;
    }

    public function setMaxHours(?int $maxHours): static
    {
        $this->maxHours = $maxHours;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return Collection<int, CalendarAppointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(CalendarAppointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setFormcalendar($this);
        }

        return $this;
    }

    public function removeAppointment(CalendarAppointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getFormcalendar() === $this) {
                $appointment->setFormcalendar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CalendarSchedule>
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(CalendarSchedule $schedule): static
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules->add($schedule);
            $schedule->setFormcalendar($this);
        }

        return $this;
    }

    public function removeSchedule(CalendarSchedule $schedule): static
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getFormcalendar() === $this) {
                $schedule->setFormcalendar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CalendarException>
     */
    public function getExceptions(): Collection
    {
        return $this->exceptions;
    }

    public function addException(CalendarException $exception): static
    {
        if (!$this->exceptions->contains($exception)) {
            $this->exceptions->add($exception);
            $exception->setFormcalendar($this);
        }

        return $this;
    }

    public function removeException(CalendarException $exception): static
    {
        if ($this->exceptions->removeElement($exception)) {
            // set the owning side to null (unless already changed)
            if ($exception->getFormcalendar() === $this) {
                $exception->setFormcalendar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CalendarIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(CalendarIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCalendar($this);
        }

        return $this;
    }

    public function removeIntl(CalendarIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCalendar() === $this) {
                $intl->setCalendar(null);
            }
        }

        return $this;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function setForm(?Form $form): static
    {
        $this->form = $form;

        return $this;
    }
}
