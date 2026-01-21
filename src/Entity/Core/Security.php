<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseInterface;
use App\Entity\Layout\Page;
use App\Repository\Core\SecurityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Security.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_security')]
#[ORM\Entity(repositoryClass: SecurityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Security extends BaseInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private ?string $securityKey = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected array $headerData = [
        'strict-transport-security',
        'permissions-policy',
        'content-security-policy',
        'referrer-policy',
        'cross-origin-embedder-policy',
        'cross-origin-resource-policy',
        'x-xss-protection',
        'x-ua-compatible',
        'content-type-options-nosniff',
        'x-frame-options-sameorigin',
        'x-permitted-cross-domain-policies',
        'cross-origin-opener-policy',
        'access-control-allow-origin',
    ];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $secureWebsite = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $resetPasswordsByGroup = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adminRegistration = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adminRegistrationValidation = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $adminPasswordSecurity = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $adminPasswordDelay = 365;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $frontRegistration = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $frontRegistrationValidation = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected array $frontRegistrationFields = ['gender', 'lastName', 'firstName', 'email', 'plainPassword', 'agreeTerms'];

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $frontEmailConfirmation = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $frontEmailWebmaster = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $frontPasswordSecurity = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $frontCustomTemplate = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\NotBlank]
    private int $frontPasswordDelay = 365;

    #[ORM\OneToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(name: 'front_page_redirection_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Page $frontPageRedirection = null;

    #[ORM\OneToOne(targetEntity: Website::class, mappedBy: 'security')]
    private ?Website $website = null;

    /**
     * @throws \Exception
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->securityKey = str_replace(['/', '.'], '', crypt(random_bytes(30), 'rl'));
        parent::prePersist();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSecurityKey(): ?string
    {
        return $this->securityKey;
    }

    public function setSecurityKey(string $securityKey): static
    {
        $this->securityKey = $securityKey;

        return $this;
    }

    public function getHeaderData(): ?array
    {
        return $this->headerData;
    }

    public function setHeaderData(?array $headerData): static
    {
        $this->headerData = $headerData;

        return $this;
    }

    public function isSecureWebsite(): ?bool
    {
        return $this->secureWebsite;
    }

    public function setSecureWebsite(bool $secureWebsite): static
    {
        $this->secureWebsite = $secureWebsite;

        return $this;
    }

    public function isResetPasswordsByGroup(): ?bool
    {
        return $this->resetPasswordsByGroup;
    }

    public function setResetPasswordsByGroup(bool $resetPasswordsByGroup): static
    {
        $this->resetPasswordsByGroup = $resetPasswordsByGroup;

        return $this;
    }

    public function isAdminRegistration(): ?bool
    {
        return $this->adminRegistration;
    }

    public function setAdminRegistration(bool $adminRegistration): static
    {
        $this->adminRegistration = $adminRegistration;

        return $this;
    }

    public function isAdminRegistrationValidation(): ?bool
    {
        return $this->adminRegistrationValidation;
    }

    public function setAdminRegistrationValidation(bool $adminRegistrationValidation): static
    {
        $this->adminRegistrationValidation = $adminRegistrationValidation;

        return $this;
    }

    public function isAdminPasswordSecurity(): ?bool
    {
        return $this->adminPasswordSecurity;
    }

    public function setAdminPasswordSecurity(bool $adminPasswordSecurity): static
    {
        $this->adminPasswordSecurity = $adminPasswordSecurity;

        return $this;
    }

    public function getAdminPasswordDelay(): ?int
    {
        return $this->adminPasswordDelay;
    }

    public function setAdminPasswordDelay(?int $adminPasswordDelay): static
    {
        $this->adminPasswordDelay = $adminPasswordDelay;

        return $this;
    }

    public function isFrontRegistration(): ?bool
    {
        return $this->frontRegistration;
    }

    public function setFrontRegistration(bool $frontRegistration): static
    {
        $this->frontRegistration = $frontRegistration;

        return $this;
    }

    public function isFrontRegistrationValidation(): ?bool
    {
        return $this->frontRegistrationValidation;
    }

    public function setFrontRegistrationValidation(bool $frontRegistrationValidation): static
    {
        $this->frontRegistrationValidation = $frontRegistrationValidation;

        return $this;
    }

    public function getFrontRegistrationFields(): ?array
    {
        return $this->frontRegistrationFields;
    }

    public function setFrontRegistrationFields(?array $frontRegistrationFields): static
    {
        $this->frontRegistrationFields = $frontRegistrationFields;

        return $this;
    }

    public function isFrontEmailConfirmation(): ?bool
    {
        return $this->frontEmailConfirmation;
    }

    public function setFrontEmailConfirmation(bool $frontEmailConfirmation): static
    {
        $this->frontEmailConfirmation = $frontEmailConfirmation;

        return $this;
    }

    public function isFrontEmailWebmaster(): ?bool
    {
        return $this->frontEmailWebmaster;
    }

    public function setFrontEmailWebmaster(bool $frontEmailWebmaster): static
    {
        $this->frontEmailWebmaster = $frontEmailWebmaster;

        return $this;
    }

    public function isFrontPasswordSecurity(): ?bool
    {
        return $this->frontPasswordSecurity;
    }

    public function setFrontPasswordSecurity(bool $frontPasswordSecurity): static
    {
        $this->frontPasswordSecurity = $frontPasswordSecurity;

        return $this;
    }

    public function isFrontCustomTemplate(): ?bool
    {
        return $this->frontCustomTemplate;
    }

    public function setFrontCustomTemplate(bool $frontCustomTemplate): static
    {
        $this->frontCustomTemplate = $frontCustomTemplate;

        return $this;
    }

    public function getFrontPasswordDelay(): ?int
    {
        return $this->frontPasswordDelay;
    }

    public function setFrontPasswordDelay(?int $frontPasswordDelay): static
    {
        $this->frontPasswordDelay = $frontPasswordDelay;

        return $this;
    }

    public function getFrontPageRedirection(): ?Page
    {
        return $this->frontPageRedirection;
    }

    public function setFrontPageRedirection(?Page $frontPageRedirection): static
    {
        $this->frontPageRedirection = $frontPageRedirection;

        return $this;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): static
    {
        // unset the owning side of the relation if necessary
        if ($website === null && $this->website !== null) {
            $this->website->setSecurity(null);
        }

        // set the owning side of the relation if necessary
        if ($website !== null && $website->getSecurity() !== $this) {
            $website->setSecurity($this);
        }

        $this->website = $website;

        return $this;
    }
}
