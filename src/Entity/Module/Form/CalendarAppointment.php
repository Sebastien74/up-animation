<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseEntity;
use App\Repository\Module\Form\CalendarAppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * CalendarAppointment.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_calendar_appointment')]
#[ORM\Entity(repositoryClass: CalendarAppointmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CalendarAppointment extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'formcalendar';
    protected static array $interface = [
        'name' => 'formcalendarappointment',
    ];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $appointmentDate = null;

    #[ORM\OneToOne(targetEntity: ContactForm::class, inversedBy: 'appointment', cascade: ['persist'])]
    private ?ContactForm $contactForm = null;

    #[ORM\ManyToOne(targetEntity: Calendar::class, inversedBy: 'appointments')]
    private ?Calendar $formcalendar = null;

    public function getAppointmentDate(): ?\DateTimeInterface
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(?\DateTimeInterface $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;

        return $this;
    }

    public function getContactForm(): ?ContactForm
    {
        return $this->contactForm;
    }

    public function setContactForm(?ContactForm $contactForm): static
    {
        $this->contactForm = $contactForm;

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
