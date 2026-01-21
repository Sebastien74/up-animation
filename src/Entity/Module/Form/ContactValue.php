<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\Layout\FieldConfiguration;
use App\Repository\Module\Form\ContactValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ContactValue.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_contact_value')]
#[ORM\Entity(repositoryClass: ContactValueRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContactValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(targetEntity: ContactForm::class, inversedBy: 'contactValues')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ContactForm $contactForm = null;

    #[ORM\ManyToOne(targetEntity: ContactStepForm::class, inversedBy: 'contactValues')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ContactStepForm $contactStepForm = null;

    #[ORM\ManyToOne(targetEntity: FieldConfiguration::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'cascade')]
    private ?FieldConfiguration $configuration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function setDate(?\DateTimeInterface $date = null): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
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

    public function getContactStepForm(): ?ContactStepForm
    {
        return $this->contactStepForm;
    }

    public function setContactStepForm(?ContactStepForm $contactStepForm): static
    {
        $this->contactStepForm = $contactStepForm;

        return $this;
    }

    public function getConfiguration(): ?FieldConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?FieldConfiguration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
