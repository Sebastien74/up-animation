<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Repository\Api\GoogleIntlRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * GoogleIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'api_google_intl')]
#[ORM\Entity(repositoryClass: GoogleIntlRepository::class)]
class GoogleIntl
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clientId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $analyticsUa = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $analyticsAccountId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $analyticsStatsDuration = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $tagManagerKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tagManagerLayer = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $searchConsoleKey = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $serverUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $mapKey = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $placeId = null;

    #[ORM\ManyToOne(targetEntity: Google::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Google $google = null;

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

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): static
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getAnalyticsUa(): ?string
    {
        return $this->analyticsUa;
    }

    public function setAnalyticsUa(?string $analyticsUa): static
    {
        $this->analyticsUa = $analyticsUa;

        return $this;
    }

    public function getAnalyticsAccountId(): ?string
    {
        return $this->analyticsAccountId;
    }

    public function setAnalyticsAccountId(?string $analyticsAccountId): static
    {
        $this->analyticsAccountId = $analyticsAccountId;

        return $this;
    }

    public function getAnalyticsStatsDuration(): ?string
    {
        return $this->analyticsStatsDuration;
    }

    public function setAnalyticsStatsDuration(?string $analyticsStatsDuration): static
    {
        $this->analyticsStatsDuration = $analyticsStatsDuration;

        return $this;
    }

    public function getTagManagerKey(): ?string
    {
        return $this->tagManagerKey;
    }

    public function setTagManagerKey(?string $tagManagerKey): static
    {
        $this->tagManagerKey = $tagManagerKey;

        return $this;
    }

    public function getTagManagerLayer(): ?string
    {
        return $this->tagManagerLayer;
    }

    public function setTagManagerLayer(?string $tagManagerLayer): static
    {
        $this->tagManagerLayer = $tagManagerLayer;

        return $this;
    }

    public function getSearchConsoleKey(): ?string
    {
        return $this->searchConsoleKey;
    }

    public function setSearchConsoleKey(?string $searchConsoleKey): static
    {
        $this->searchConsoleKey = $searchConsoleKey;

        return $this;
    }

    public function getServerUrl(): ?string
    {
        return $this->serverUrl;
    }

    public function setServerUrl(?string $serverUrl): static
    {
        $this->serverUrl = $serverUrl;

        return $this;
    }

    public function getMapKey(): ?string
    {
        return $this->mapKey;
    }

    public function setMapKey(?string $mapKey): static
    {
        $this->mapKey = $mapKey;

        return $this;
    }

    public function getPlaceId(): ?string
    {
        return $this->placeId;
    }

    public function setPlaceId(?string $placeId): static
    {
        $this->placeId = $placeId;

        return $this;
    }

    public function getGoogle(): ?Google
    {
        return $this->google;
    }

    public function setGoogle(?Google $google): static
    {
        $this->google = $google;

        return $this;
    }
}
