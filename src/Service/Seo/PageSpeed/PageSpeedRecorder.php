<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

use App\Entity\Core\Website;
use App\Entity\Seo\PageSpeedSnapshot;
use App\Repository\Seo\PageSpeedSnapshotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * PageSpeedRecorder.
 *
 * Persists a normalized PageSpeed Insights result (dashboard scalars + full JSON
 * report) and prunes the per-page history.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedRecorder
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PageSpeedSnapshotRepository $repository,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<string, mixed> $report
     */
    public function record(Website $website, ?string $code, ?string $locale, array $report, int $keep = 20): ?PageSpeedSnapshot
    {
        try {
            $strategies = is_array($report['strategies'] ?? null) ? $report['strategies'] : [];
            $mobile = is_array($strategies['mobile'] ?? null) ? $strategies['mobile'] : null;
            $desktop = is_array($strategies['desktop'] ?? null) ? $strategies['desktop'] : null;
            $primary = $mobile ?? $desktop ?? [];

            $scores = is_array($primary['scores'] ?? null) ? $primary['scores'] : [];
            $lab = is_array($primary['lab'] ?? null) ? $primary['lab'] : [];
            $cls = $lab['cls'] ?? null;

            $snapshot = (new PageSpeedSnapshot())
                ->setWebsite($website)
                ->setUrlCode((string) $code)
                ->setLocale((string) $locale)
                ->setPerfMobile($this->intOrNull($mobile['scores']['performance'] ?? null))
                ->setPerfDesktop($this->intOrNull($desktop['scores']['performance'] ?? null))
                ->setAccessibility($this->intOrNull($scores['accessibility'] ?? null))
                ->setBestPractices($this->intOrNull($scores['bestPractices'] ?? null))
                ->setSeo($this->intOrNull($scores['seo'] ?? null))
                ->setLcpMs($this->intOrNull($lab['lcpMs'] ?? null))
                ->setTbtMs($this->intOrNull($lab['tbtMs'] ?? null))
                ->setClsX1000(null === $cls ? null : (int) round((float) $cls * 1000))
                ->setFieldData(null !== ($mobile['field'] ?? null) || null !== ($desktop['field'] ?? null))
                ->setReport($report);

            $this->entityManager->persist($snapshot);
            $this->entityManager->flush();

            $this->repository->pruneOldSnapshots($website, $code, $locale, $keep);

            return $snapshot;
        } catch (\Throwable $e) {
            $this->logger?->error('PageSpeed snapshot not recorded: '.$e->getMessage(), [
                'urlCode' => $code,
                'locale' => $locale,
                'exception' => $e,
            ]);

            return null;
        }
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
