<?php

declare(strict_types=1);

namespace App\Entity\Seo;

use App\Entity\Core\Website;
use App\Repository\Seo\PageAnalysisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * PageAnalysis.
 *
 * Immutable history snapshot of a page performance/rendering analysis (admin preview
 * tool). Standalone entity (no base class) so the table schema stays fully explicit.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'seo_page_analysis')]
#[ORM\Index(name: 'idx_seo_page_analysis_lookup', columns: ['website_id', 'urlCode', 'locale'])]
#[ORM\Entity(repositoryClass: PageAnalysisRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PageAnalysis
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

    /**
     * How the snapshot was produced: 'manual' (admin tools, preview) or 'cron' (live).
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $source = 'manual';

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $score = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $htmlKb = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $domCount = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $imagesCount = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $requests = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $renderMs = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $externalDomains = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $severityHigh = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $severityMedium = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $severityLow = 0;

    /**
     * Full structured analysis report (meta, score, summary, grouped findings) for
     * later processing.
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

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getHtmlKb(): int
    {
        return $this->htmlKb;
    }

    public function setHtmlKb(int $htmlKb): static
    {
        $this->htmlKb = $htmlKb;

        return $this;
    }

    public function getDomCount(): int
    {
        return $this->domCount;
    }

    public function setDomCount(int $domCount): static
    {
        $this->domCount = $domCount;

        return $this;
    }

    public function getImagesCount(): int
    {
        return $this->imagesCount;
    }

    public function setImagesCount(int $imagesCount): static
    {
        $this->imagesCount = $imagesCount;

        return $this;
    }

    public function getRequests(): int
    {
        return $this->requests;
    }

    public function setRequests(int $requests): static
    {
        $this->requests = $requests;

        return $this;
    }

    public function getRenderMs(): ?int
    {
        return $this->renderMs;
    }

    public function setRenderMs(?int $renderMs): static
    {
        $this->renderMs = $renderMs;

        return $this;
    }

    public function getExternalDomains(): int
    {
        return $this->externalDomains;
    }

    public function setExternalDomains(int $externalDomains): static
    {
        $this->externalDomains = $externalDomains;

        return $this;
    }

    public function getSeverityHigh(): int
    {
        return $this->severityHigh;
    }

    public function setSeverityHigh(int $severityHigh): static
    {
        $this->severityHigh = $severityHigh;

        return $this;
    }

    public function getSeverityMedium(): int
    {
        return $this->severityMedium;
    }

    public function setSeverityMedium(int $severityMedium): static
    {
        $this->severityMedium = $severityMedium;

        return $this;
    }

    public function getSeverityLow(): int
    {
        return $this->severityLow;
    }

    public function setSeverityLow(int $severityLow): static
    {
        $this->severityLow = $severityLow;

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
