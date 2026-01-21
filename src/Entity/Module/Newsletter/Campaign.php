<?php

declare(strict_types=1);

namespace App\Entity\Module\Newsletter;

use App\Entity\BaseEntity;
use App\Entity\Core\Website;
use App\Repository\Module\Newsletter\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Campaign.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newsletter_campaign')]
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Campaign extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static string $masterField = 'website';
    protected static array $interface = [
        'name' => 'campaign',
        'buttons' => [
            'admin_newsletteremail_index',
        ],
    ];
    protected static array $labels = [
        'admin_newsletteremail_index' => 'Emails',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $emailConfirmation = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $emailToWebmaster = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $receivingEmails = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $sendingEmail = 'noreply@agence-felix.fr';

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $recaptcha = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $internalRegistration = true;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $securityKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $externalFormAction = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalFieldEmail = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalFormToken = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $mailjetListName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $mailjetListId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $mailjetPublicKey = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $mailjetSecretKey = null;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Email::class, cascade: ['persist'], orphanRemoval: true)]
    private ArrayCollection|PersistentCollection $emails;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: CampaignIntl::class, cascade: ['persist', 'remove'], fetch: 'EAGER', orphanRemoval: true)]
    #[ORM\OrderBy(['locale' => 'ASC'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ArrayCollection|PersistentCollection $intls;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Website $website = null;

    /**
     * Campaign constructor.
     */
    public function __construct()
    {
        $this->emails = new ArrayCollection();
        $this->intls = new ArrayCollection();
    }

    public function isEmailConfirmation(): ?bool
    {
        return $this->emailConfirmation;
    }

    public function setEmailConfirmation(bool $emailConfirmation): static
    {
        $this->emailConfirmation = $emailConfirmation;

        return $this;
    }

    public function isEmailToWebmaster(): ?bool
    {
        return $this->emailToWebmaster;
    }

    public function setEmailToWebmaster(bool $emailToWebmaster): static
    {
        $this->emailToWebmaster = $emailToWebmaster;

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

    public function getSendingEmail(): ?string
    {
        return $this->sendingEmail;
    }

    public function setSendingEmail(?string $sendingEmail): static
    {
        $this->sendingEmail = $sendingEmail;

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

    public function isInternalRegistration(): ?bool
    {
        return $this->internalRegistration;
    }

    public function setInternalRegistration(bool $internalRegistration): static
    {
        $this->internalRegistration = $internalRegistration;

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

    public function getExternalFormAction(): ?string
    {
        return $this->externalFormAction;
    }

    public function setExternalFormAction(?string $externalFormAction): static
    {
        $this->externalFormAction = $externalFormAction;

        return $this;
    }

    public function getExternalFieldEmail(): ?string
    {
        return $this->externalFieldEmail;
    }

    public function setExternalFieldEmail(?string $externalFieldEmail): static
    {
        $this->externalFieldEmail = $externalFieldEmail;

        return $this;
    }

    public function getExternalFormToken(): ?string
    {
        return $this->externalFormToken;
    }

    public function setExternalFormToken(?string $externalFormToken): static
    {
        $this->externalFormToken = $externalFormToken;

        return $this;
    }

    public function getMailjetListName(): ?string
    {
        return $this->mailjetListName;
    }

    public function setMailjetListName(?string $mailjetListName): static
    {
        $this->mailjetListName = $mailjetListName;

        return $this;
    }

    public function getMailjetListId(): ?string
    {
        return $this->mailjetListId;
    }

    public function setMailjetListId(?string $mailjetListId): static
    {
        $this->mailjetListId = $mailjetListId;

        return $this;
    }

    public function getMailjetPublicKey(): ?string
    {
        return $this->mailjetPublicKey;
    }

    public function setMailjetPublicKey(?string $mailjetPublicKey): static
    {
        $this->mailjetPublicKey = $mailjetPublicKey;

        return $this;
    }

    public function getMailjetSecretKey(): ?string
    {
        return $this->mailjetSecretKey;
    }

    public function setMailjetSecretKey(?string $mailjetSecretKey): static
    {
        $this->mailjetSecretKey = $mailjetSecretKey;

        return $this;
    }

    /**
     * @return Collection<int, Email>
     */
    public function getEmails(): Collection
    {
        return $this->emails;
    }

    public function addEmail(Email $email): static
    {
        if (!$this->emails->contains($email)) {
            $this->emails->add($email);
            $email->setCampaign($this);
        }

        return $this;
    }

    public function removeEmail(Email $email): static
    {
        if ($this->emails->removeElement($email)) {
            // set the owning side to null (unless already changed)
            if ($email->getCampaign() === $this) {
                $email->setCampaign(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CampaignIntl>
     */
    public function getIntls(): Collection
    {
        return $this->intls;
    }

    public function addIntl(CampaignIntl $intl): static
    {
        if (!$this->intls->contains($intl)) {
            $this->intls->add($intl);
            $intl->setCampaign($this);
        }

        return $this;
    }

    public function removeIntl(CampaignIntl $intl): static
    {
        if ($this->intls->removeElement($intl)) {
            // set the owning side to null (unless already changed)
            if ($intl->getCampaign() === $this) {
                $intl->setCampaign(null);
            }
        }

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }
}
