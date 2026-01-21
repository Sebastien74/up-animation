<?php

declare(strict_types=1);

namespace App\Entity\Module\Form;

use App\Entity\Layout\Page;
use App\Repository\Module\Form\ConfigurationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ConfigurationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_form_configuration')]
#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Configuration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $sendingEmail = 'noreply@agence-felix.fr';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $receivingEmails = ['dev@agence-felix.fr'];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $dbRegistration = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $ajax = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $thanksModal = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $thanksPage = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $attachmentsInMail = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $confirmEmail = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $uniqueContact = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $recaptcha = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $calendarsActive = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $dynamic = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $floatingLabels = true;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $securityKey = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $maxShipments = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publicationEnd = null;

    #[ORM\OneToOne(targetEntity: Form::class, inversedBy: 'configuration', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Form $form = null;

    #[ORM\OneToOne(targetEntity: StepForm::class, inversedBy: 'configuration', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?StepForm $stepform = null;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Page $pageRedirection = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSendingEmail(): ?string
    {
        return $this->sendingEmail;
    }

    public function setSendingEmail(?string $sendingEmail): static
    {
        $this->sendingEmail = $sendingEmail;

        return $this;
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

    public function isDbRegistration(): ?bool
    {
        return $this->dbRegistration;
    }

    public function setDbRegistration(bool $dbRegistration): static
    {
        $this->dbRegistration = $dbRegistration;

        return $this;
    }

    public function isAjax(): ?bool
    {
        return $this->ajax;
    }

    public function setAjax(bool $ajax): static
    {
        $this->ajax = $ajax;

        return $this;
    }

    public function isThanksModal(): ?bool
    {
        return $this->thanksModal;
    }

    public function setThanksModal(bool $thanksModal): static
    {
        $this->thanksModal = $thanksModal;

        return $this;
    }

    public function isThanksPage(): ?bool
    {
        return $this->thanksPage;
    }

    public function setThanksPage(bool $thanksPage): static
    {
        $this->thanksPage = $thanksPage;

        return $this;
    }

    public function isAttachmentsInMail(): ?bool
    {
        return $this->attachmentsInMail;
    }

    public function setAttachmentsInMail(bool $attachmentsInMail): static
    {
        $this->attachmentsInMail = $attachmentsInMail;

        return $this;
    }

    public function isConfirmEmail(): ?bool
    {
        return $this->confirmEmail;
    }

    public function setConfirmEmail(bool $confirmEmail): static
    {
        $this->confirmEmail = $confirmEmail;

        return $this;
    }

    public function isUniqueContact(): ?bool
    {
        return $this->uniqueContact;
    }

    public function setUniqueContact(bool $uniqueContact): static
    {
        $this->uniqueContact = $uniqueContact;

        return $this;
    }

    public function isRecaptcha(): ?bool
    {
        return $this->recaptcha;
    }

    public function setRecaptcha(bool $recaptcha): static
    {
        $this->recaptcha = $recaptcha;

        return $this;
    }

    public function isCalendarsActive(): ?bool
    {
        return $this->calendarsActive;
    }

    public function setCalendarsActive(bool $calendarsActive): static
    {
        $this->calendarsActive = $calendarsActive;

        return $this;
    }

    public function isDynamic(): ?bool
    {
        return $this->dynamic;
    }

    public function setDynamic(bool $dynamic): static
    {
        $this->dynamic = $dynamic;

        return $this;
    }

    public function isFloatingLabels(): ?bool
    {
        return $this->floatingLabels;
    }

    public function setFloatingLabels(bool $floatingLabels): static
    {
        $this->floatingLabels = $floatingLabels;

        return $this;
    }

    public function getSecurityKey(): ?string
    {
        return $this->securityKey;
    }

    public function setSecurityKey(?string $securityKey): static
    {
        $this->securityKey = $securityKey;

        return $this;
    }

    public function getMaxShipments(): ?int
    {
        return $this->maxShipments;
    }

    public function setMaxShipments(?int $maxShipments): static
    {
        $this->maxShipments = $maxShipments;

        return $this;
    }

    public function getPublicationStart(): ?\DateTimeInterface
    {
        return $this->publicationStart;
    }

    public function setPublicationStart(?\DateTimeInterface $publicationStart): static
    {
        $this->publicationStart = $publicationStart;

        return $this;
    }

    public function getPublicationEnd(): ?\DateTimeInterface
    {
        return $this->publicationEnd;
    }

    public function setPublicationEnd(?\DateTimeInterface $publicationEnd): static
    {
        $this->publicationEnd = $publicationEnd;

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

    public function getStepform(): ?StepForm
    {
        return $this->stepform;
    }

    public function setStepform(?StepForm $stepform): static
    {
        $this->stepform = $stepform;

        return $this;
    }

    public function getPageRedirection(): ?Page
    {
        return $this->pageRedirection;
    }

    public function setPageRedirection(?Page $pageRedirection): static
    {
        $this->pageRedirection = $pageRedirection;

        return $this;
    }
}
