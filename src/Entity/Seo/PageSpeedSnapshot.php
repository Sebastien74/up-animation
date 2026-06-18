<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\Core\Website;
use App\Repository\Seo\PageSpeedSnapshotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * PageSpeedSnapshot.
 *
 * Immutable history snapshot of a Google PageSpeed Insights run (real Lighthouse lab
 * scores + CrUX field data) for one front page. Standalone entity (no base class) so
 * the schema stays fully explicit.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_pagespeed_snapshot')]
#[ORM\Index(name: 'idx_seo_pagespeed_lookup', columns: ['website_id', 'urlCode', 'locale'])]
#[ORM\Entity(repositoryClass: PageSpeedSnapshotRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PageSpeedSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Website::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Website $website = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $urlCode = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $perfMobile = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $perfDesktop = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $accessibility = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $bestPractices = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $seo = null;

    /**
     * Mobile lab Largest Contentful Paint, in milliseconds.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $lcpMs = null;

    /**
     * Mobile lab Total Blocking Time, in milliseconds.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tbtMs = null;

    /**
     * Mobile lab Cumulative Layout Shift x1000 (stored as int to avoid float drift).
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $clsX1000 = null;

    /**
     * Whether CrUX field data (real-user metrics) was available for the URL/origin.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $fieldData = false;

    /**
     * Full normalized result (per strategy: category scores, lab + field metrics,
     * actionable audits with code-source mapping) for later processing.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $report = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUrlCode(): ?string
    {
        return $this->urlCode;
    }

    public function setUrlCode(?string $urlCode): static
    {
        $this->urlCode = $urlCode;

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

    public function getPerfMobile(): ?int
    {
        return $this->perfMobile;
    }

    public function setPerfMobile(?int $perfMobile): static
    {
        $this->perfMobile = $perfMobile;

        return $this;
    }

    public function getPerfDesktop(): ?int
    {
        return $this->perfDesktop;
    }

    public function setPerfDesktop(?int $perfDesktop): static
    {
        $this->perfDesktop = $perfDesktop;

        return $this;
    }

    public function getAccessibility(): ?int
    {
        return $this->accessibility;
    }

    public function setAccessibility(?int $accessibility): static
    {
        $this->accessibility = $accessibility;

        return $this;
    }

    public function getBestPractices(): ?int
    {
        return $this->bestPractices;
    }

    public function setBestPractices(?int $bestPractices): static
    {
        $this->bestPractices = $bestPractices;

        return $this;
    }

    public function getSeo(): ?int
    {
        return $this->seo;
    }

    public function setSeo(?int $seo): static
    {
        $this->seo = $seo;

        return $this;
    }

    public function getLcpMs(): ?int
    {
        return $this->lcpMs;
    }

    public function setLcpMs(?int $lcpMs): static
    {
        $this->lcpMs = $lcpMs;

        return $this;
    }

    public function getTbtMs(): ?int
    {
        return $this->tbtMs;
    }

    public function setTbtMs(?int $tbtMs): static
    {
        $this->tbtMs = $tbtMs;

        return $this;
    }

    public function getClsX1000(): ?int
    {
        return $this->clsX1000;
    }

    public function setClsX1000(?int $clsX1000): static
    {
        $this->clsX1000 = $clsX1000;

        return $this;
    }

    public function isFieldData(): bool
    {
        return $this->fieldData;
    }

    public function setFieldData(bool $fieldData): static
    {
        $this->fieldData = $fieldData;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getReport(): ?array
    {
        return $this->report;
    }

    /**
     * @param array<string, mixed>|null $report
     */
    public function setReport(?array $report): static
    {
        $this->report = $report;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
