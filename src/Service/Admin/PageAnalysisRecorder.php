<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Core\Website;
use App\Entity\Seo\PageAnalysis;
use App\Repository\Seo\PageAnalysisRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PageAnalysisRecorder.
 *
 * Persists a page analysis snapshot (aggregates + full JSON report) and prunes the
 * history. Shared by the interactive admin tools and the periodic cron command.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageAnalysisRecorder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PageAnalysisRepository $repository,
    ) {
    }

    /**
     * Best-effort: never throws, returns null on persistence failure.
     *
     * @param array<string, mixed> $report
     * @param string               $source 'manual' (admin tools) or 'cron'
     */
    public function record(Website $website, ?string $code, ?string $locale, array $report, string $source = 'manual', int $keep = 20): ?PageAnalysis
    {
        try {
            $meta = $report['meta'] ?? [];
            $summary = $report['summary'] ?? [];
            $snapshot = (new PageAnalysis())
                ->setWebsite($website)
                ->setUrlCode((string) $code)
                ->setLocale((string) $locale)
                ->setSource($source)
                ->setScore($report['score'] ?? null)
                ->setHtmlKb((int) ($meta['kb'] ?? 0))
                ->setDomCount((int) ($meta['dom'] ?? 0))
                ->setImagesCount((int) ($meta['images'] ?? 0))
                ->setRequests((int) ($meta['requests'] ?? 0))
                ->setRenderMs(isset($meta['renderMs']) ? (int) $meta['renderMs'] : null)
                ->setExternalDomains((int) ($meta['externalDomains'] ?? 0))
                ->setSeverityHigh((int) ($summary['high'] ?? 0))
                ->setSeverityMedium((int) ($summary['medium'] ?? 0))
                ->setSeverityLow((int) ($summary['low'] ?? 0))
                ->setReport($report);

            $this->entityManager->persist($snapshot);
            $this->entityManager->flush();

            $this->repository->pruneOldSnapshots($website, $code, $locale, $keep);

            return $snapshot;
        } catch (\Throwable) {
            return null;
        }
    }
}
