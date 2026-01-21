<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\BaseInterface;
use App\Repository\Module\Form\ContactFormRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * ContactForm.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_contact')]
#[ORM\Entity(repositoryClass: ContactFormRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContactForm extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'form';
    protected static array $interface = [
        'name' => 'formcontact',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $tokenExpired = false;

    #[ORM\OneToOne(targetEntity: CalendarAppointment::class, mappedBy: 'contactForm', cascade: ['persist', 'remove'])]
    private ?CalendarAppointment $appointment = null;

    #[ORM\OneToMany(targetEntity: ContactValue::class, mappedBy: 'contactForm', cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $contactValues;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Form $form = null;

    #[ORM\ManyToOne(targetEntity: Calendar::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Calendar $calendar = null;

    /**
     * ContactForm constructor.
     */
    public function __construct()
    {
        $this->contactValues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function isTokenExpired(): ?bool
    {
        return $this->tokenExpired;
    }

    public function setTokenExpired(bool $tokenExpired): static
    {
        $this->tokenExpired = $tokenExpired;

        return $this;
    }

    public function getAppointment(): ?CalendarAppointment
    {
        return $this->appointment;
    }

    public function setAppointment(?CalendarAppointment $appointment): static
    {
        // unset the owning side of the relation if necessary
        if ($appointment === null && $this->appointment !== null) {
            $this->appointment->setContactForm(null);
        }

        // set the owning side of the relation if necessary
        if ($appointment !== null && $appointment->getContactForm() !== $this) {
            $appointment->setContactForm($this);
        }

        $this->appointment = $appointment;

        return $this;
    }

    /**
     * @return Collection<int, ContactValue>
     */
    public function getContactValues(): Collection
    {
        return $this->contactValues;
    }

    public function addContactValue(ContactValue $contactValue): static
    {
        if (!$this->contactValues->contains($contactValue)) {
            $this->contactValues->add($contactValue);
            $contactValue->setContactForm($this);
        }

        return $this;
    }

    public function removeContactValue(ContactValue $contactValue): static
    {
        if ($this->contactValues->removeElement($contactValue)) {
            // set the owning side to null (unless already changed)
            if ($contactValue->getContactForm() === $this) {
                $contactValue->setContactForm(null);
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
