<?php

declare(strict_types=1);

namespace App\Entity\Security;

use App\Entity\BaseEntity;
use App\Repository\Security\CompanyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Company.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'security_company')]
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Company extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'securitycompany',
    ];

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactLastName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactFirstName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    #[Assert\Url]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $secretKey = null;

    #[ORM\OneToOne(targetEntity: Logo::class, mappedBy: 'company', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?Logo $logo = null;

    #[ORM\OneToOne(targetEntity: CompanyAddress::class, mappedBy: 'company', cascade: ['persist', 'remove'])]
    #[Assert\Valid(['groups' => ['form_submission']])]
    private ?CompanyAddress $address = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->secretKey = md5(uniqid().$this->name);

        parent::prePersist();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getContactLastName(): ?string
    {
        return $this->contactLastName;
    }

    public function setContactLastName(?string $contactLastName): static
    {
        $this->contactLastName = $contactLastName;

        return $this;
    }

    public function getContactFirstName(): ?string
    {
        return $this->contactFirstName;
    }

    public function setContactFirstName(?string $contactFirstName): static
    {
        $this->contactFirstName = $contactFirstName;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): static
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): static
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getLogo(): ?Logo
    {
        return $this->logo;
    }

    public function setLogo(?Logo $logo): static
    {
        // unset the owning side of the relation if necessary
        if ($logo === null && $this->logo !== null) {
            $this->logo->setCompany(null);
        }

        // set the owning side of the relation if necessary
        if ($logo !== null && $logo->getCompany() !== $this) {
            $logo->setCompany($this);
        }

        $this->logo = $logo;

        return $this;
    }

    public function getAddress(): ?CompanyAddress
    {
        return $this->address;
    }

    public function setAddress(?CompanyAddress $address): static
    {
        // unset the owning side of the relation if necessary
        if ($address === null && $this->address !== null) {
            $this->address->setCompany(null);
        }

        // set the owning side of the relation if necessary
        if ($address !== null && $address->getCompany() !== $this) {
            $address->setCompany($this);
        }

        $this->address = $address;

        return $this;
    }
}
