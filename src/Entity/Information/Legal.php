<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseInterface;
use App\Repository\Information\LegalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Legal.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'legal')]
#[ORM\Entity(repositoryClass: LegalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Legal extends BaseInterface
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'legal',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $companyRepresentativeName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $capital = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $siretNumber = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $commercialRegisterNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $companyAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $managerName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $managerEmail = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $webmasterName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $webmasterEmail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $hostName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $hostAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $protectionOfficerName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $protectionOfficerEmail = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $protectionOfficerAddress = null;

    #[ORM\ManyToOne(targetEntity: Information::class, cascade: ['persist'], inversedBy: 'legals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Information $information = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCompanyRepresentativeName(): ?string
    {
        return $this->companyRepresentativeName;
    }

    public function setCompanyRepresentativeName(?string $companyRepresentativeName): static
    {
        $this->companyRepresentativeName = $companyRepresentativeName;

        return $this;
    }

    public function getCapital(): ?string
    {
        return $this->capital;
    }

    public function setCapital(?string $capital): static
    {
        $this->capital = $capital;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getSiretNumber(): ?string
    {
        return $this->siretNumber;
    }

    public function setSiretNumber(?string $siretNumber): static
    {
        $this->siretNumber = $siretNumber;

        return $this;
    }

    public function getCommercialRegisterNumber(): ?string
    {
        return $this->commercialRegisterNumber;
    }

    public function setCommercialRegisterNumber(?string $commercialRegisterNumber): static
    {
        $this->commercialRegisterNumber = $commercialRegisterNumber;

        return $this;
    }

    public function getCompanyAddress(): ?string
    {
        return $this->companyAddress;
    }

    public function setCompanyAddress(?string $companyAddress): static
    {
        $this->companyAddress = $companyAddress;

        return $this;
    }

    public function getManagerName(): ?string
    {
        return $this->managerName;
    }

    public function setManagerName(?string $managerName): static
    {
        $this->managerName = $managerName;

        return $this;
    }

    public function getManagerEmail(): ?string
    {
        return $this->managerEmail;
    }

    public function setManagerEmail(?string $managerEmail): static
    {
        $this->managerEmail = $managerEmail;

        return $this;
    }

    public function getWebmasterName(): ?string
    {
        return $this->webmasterName;
    }

    public function setWebmasterName(?string $webmasterName): static
    {
        $this->webmasterName = $webmasterName;

        return $this;
    }

    public function getWebmasterEmail(): ?string
    {
        return $this->webmasterEmail;
    }

    public function setWebmasterEmail(?string $webmasterEmail): static
    {
        $this->webmasterEmail = $webmasterEmail;

        return $this;
    }

    public function getHostName(): ?string
    {
        return $this->hostName;
    }

    public function setHostName(?string $hostName): static
    {
        $this->hostName = $hostName;

        return $this;
    }

    public function getHostAddress(): ?string
    {
        return $this->hostAddress;
    }

    public function setHostAddress(?string $hostAddress): static
    {
        $this->hostAddress = $hostAddress;

        return $this;
    }

    public function getProtectionOfficerName(): ?string
    {
        return $this->protectionOfficerName;
    }

    public function setProtectionOfficerName(?string $protectionOfficerName): static
    {
        $this->protectionOfficerName = $protectionOfficerName;

        return $this;
    }

    public function getProtectionOfficerEmail(): ?string
    {
        return $this->protectionOfficerEmail;
    }

    public function setProtectionOfficerEmail(?string $protectionOfficerEmail): static
    {
        $this->protectionOfficerEmail = $protectionOfficerEmail;

        return $this;
    }

    public function getProtectionOfficerAddress(): ?string
    {
        return $this->protectionOfficerAddress;
    }

    public function setProtectionOfficerAddress(?string $protectionOfficerAddress): static
    {
        $this->protectionOfficerAddress = $protectionOfficerAddress;

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
