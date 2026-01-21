<?php

declare(strict_types=1);

namespace App\Entity\Information;

use App\Entity\BaseEntity;
use App\Repository\Information\PhoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Phone.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'information_phone')]
#[ORM\Entity(repositoryClass: PhoneRepository::class)]
class Phone extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'phone',
    ];

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $entitled = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $tagNumber = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $zones = [];

    #[ORM\ManyToOne(targetEntity: Address::class, cascade: ['persist'], inversedBy: 'phones')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Address $address = null;

    #[ORM\ManyToOne(targetEntity: Information::class, cascade: ['persist'], inversedBy: 'phones')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Information $information = null;

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getEntitled(): ?string
    {
        return $this->entitled;
    }

    public function setEntitled(?string $entitled): static
    {
        $this->entitled = $entitled;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getTagNumber(): ?string
    {
        return $this->tagNumber;
    }

    public function setTagNumber(?string $tagNumber): static
    {
        $this->tagNumber = $tagNumber;

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

    public function getZones(): ?array
    {
        return $this->zones;
    }

    public function setZones(?array $zones): static
    {
        $this->zones = $zones;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;

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
