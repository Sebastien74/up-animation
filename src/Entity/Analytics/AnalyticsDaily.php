<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use App\Repository\Analytics\AnalyticsDailyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * AnalyticsDaily.
 *
 * Daily aggregates of raw events, kept indefinitely.
 * No session identifier persisted: pure counters only.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'analytics_daily')]
#[ORM\Entity(repositoryClass: AnalyticsDailyRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_analytics_daily_dim',
    columns: ['websiteId', 'bucketDate', 'urlPath', 'countryCode', 'device', 'locale']
)]
#[ORM\Index(name: 'idx_analytics_daily_site_date', columns: ['websiteId', 'bucketDate'])]
#[ORM\Index(name: 'idx_analytics_daily_locale', columns: ['websiteId', 'locale', 'bucketDate'])]
class AnalyticsDaily
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $websiteId = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $bucketDate = null;

    #[ORM\Column(type: Types::STRING, length: 512)]
    private string $urlPath = '';

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true, options: ['fixed' => true])]
    private ?string $countryCode = null;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $device = null;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private int $visitors = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private int $sessions = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private int $pageviews = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private int $bounces = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private int $durationSum = 0;

    public function getId(): ?string
    {
        return $this->id;
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

    public function getBucketDate(): ?\DateTimeImmutable
    {
        return $this->bucketDate;
    }

    public function setBucketDate(\DateTimeImmutable $bucketDate): static
    {
        $this->bucketDate = $bucketDate;

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

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getVisitors(): int
    {
        return $this->visitors;
    }

    public function setVisitors(int $visitors): static
    {
        $this->visitors = $visitors;

        return $this;
    }

    public function getSessions(): int
    {
        return $this->sessions;
    }

    public function setSessions(int $sessions): static
    {
        $this->sessions = $sessions;

        return $this;
    }

    public function getPageviews(): int
    {
        return $this->pageviews;
    }

    public function setPageviews(int $pageviews): static
    {
        $this->pageviews = $pageviews;

        return $this;
    }

    public function getBounces(): int
    {
        return $this->bounces;
    }

    public function setBounces(int $bounces): static
    {
        $this->bounces = $bounces;

        return $this;
    }

    public function getDurationSum(): int
    {
        return $this->durationSum;
    }

    public function setDurationSum(int $durationSum): static
    {
        $this->durationSum = $durationSum;

        return $this;
    }
}