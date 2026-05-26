<?php

declare(strict_types=1);

namespace App\Entity\Analytics;

use App\Repository\Analytics\AnalyticsHourlyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * AnalyticsHourly.
 *
 * Hourly aggregates of raw events, kept 12 months.
 * Populated by app:analytics:rollup nightly cron.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'analytics_hourly')]
#[ORM\Entity(repositoryClass: AnalyticsHourlyRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_analytics_hourly_dim',
    columns: ['websiteId', 'bucketAt', 'urlPath', 'countryCode', 'device', 'locale']
)]
#[ORM\Index(name: 'idx_analytics_hourly_site_time', columns: ['websiteId', 'bucketAt'])]
#[ORM\Index(name: 'idx_analytics_hourly_locale', columns: ['websiteId', 'locale', 'bucketAt'])]
class AnalyticsHourly
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $websiteId = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $bucketAt = null;

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

    public function getBucketAt(): ?\DateTimeImmutable
    {
        return $this->bucketAt;
    }

    public function setBucketAt(\DateTimeImmutable $bucketAt): static
    {
        $this->bucketAt = $bucketAt;

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