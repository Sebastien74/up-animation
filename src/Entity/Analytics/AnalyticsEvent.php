<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use App\Repository\Analytics\AnalyticsEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * AnalyticsEvent.
 *
 * Immutable raw event captured by the analytics tracker.
 * Stored 30 days then dropped by partition rotation.
 * No personal data: sessionHash is anonymous (rotating daily salt).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'analytics_event')]
#[ORM\Entity(repositoryClass: AnalyticsEventRepository::class, readOnly: true)]
#[ORM\Index(name: 'idx_analytics_event_site_time', columns: ['websiteId', 'occurredAt'])]
#[ORM\Index(name: 'idx_analytics_event_session', columns: ['sessionHash'])]
class AnalyticsEvent
{
    public const string TYPE_PAGEVIEW = 'pageview';
    public const string TYPE_CLICK = 'click';
    public const string TYPE_SCROLL = 'scroll';
    public const string TYPE_FORM = 'form';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, precision: 3)]
    private ?\DateTimeImmutable $occurredAt = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $websiteId = 0;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private string $sessionHash = '';

    #[ORM\Column(type: Types::STRING, length: 16)]
    private string $eventType = '';

    #[ORM\Column(type: Types::STRING, length: 512)]
    private string $urlPath = '';

    #[ORM\Column(type: Types::STRING, length: 190, nullable: true)]
    private ?string $referrerDomain = null;

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true, options: ['fixed' => true])]
    private ?string $countryCode = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $device = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $browser = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $os = null;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $viewport = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $eventPayload = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOccurredAt(): ?\DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    public function setWebsiteId(int $websiteId): static
    {
        $this->websiteId = $websiteId;

        return $this;
    }

    public function getSessionHash(): string
    {
        return $this->sessionHash;
    }

    public function setSessionHash(string $sessionHash): static
    {
        $this->sessionHash = $sessionHash;

        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getUrlPath(): string
    {
        return $this->urlPath;
    }

    public function setUrlPath(string $urlPath): static
    {
        $this->urlPath = $urlPath;

        return $this;
    }

    public function getReferrerDomain(): ?string
    {
        return $this->referrerDomain;
    }

    public function setReferrerDomain(?string $referrerDomain): static
    {
        $this->referrerDomain = $referrerDomain;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getDevice(): ?string
    {
        return $this->device;
    }

    public function setDevice(?string $device): static
    {
        $this->device = $device;

        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): static
    {
        $this->browser = $browser;

        return $this;
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    public function setOs(?string $os): static
    {
        $this->os = $os;

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

    public function getViewport(): ?string
    {
        return $this->viewport;
    }

    public function setViewport(?string $viewport): static
    {
        $this->viewport = $viewport;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEventPayload(): ?array
    {
        return $this->eventPayload;
    }

    /**
     * @param array<string, mixed>|null $eventPayload
     */
    public function setEventPayload(?array $eventPayload): static
    {
        $this->eventPayload = $eventPayload;

        return $this;
    }
}