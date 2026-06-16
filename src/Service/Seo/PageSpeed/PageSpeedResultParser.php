<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

/**
 * PageSpeedResultParser.
 *
 * Normalizes a raw PageSpeed Insights (Lighthouse v5) payload into a compact,
 * storable structure: category scores, lab Core Web Vitals, CrUX field data and the
 * actionable audits enriched with their code-source mapping.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedResultParser
{
    private const int MAX_ITEMS_PER_AUDIT = 8;

    /**
     * Performance audits worth surfacing with their offending resources. Titles and
     * descriptions are taken from the (localized) PSI payload, not hardcoded here.
     */
    private const array ACTIONABLE_AUDITS = [
        'render-blocking-resources',
        'unused-css-rules',
        'unused-javascript',
        'unminified-css',
        'unminified-javascript',
        'uses-optimized-images',
        'modern-image-formats',
        'uses-responsive-images',
        'offscreen-images',
        'efficient-animated-content',
        'legacy-javascript',
        'duplicated-javascript',
        'uses-text-compression',
        'uses-long-cache-ttl',
        'server-response-time',
        'third-party-summary',
        'bootup-time',
        'mainthread-work-breakdown',
        'dom-size',
        'total-byte-weight',
        'unsized-images',
        'prioritize-lcp-image',
    ];

    public function __construct(private readonly PageSpeedSourceMapper $sourceMapper)
    {
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function parse(array $raw, ?string $ownHost = null): array
    {
        $lighthouse = is_array($raw['lighthouseResult'] ?? null) ? $raw['lighthouseResult'] : [];
        $categories = is_array($lighthouse['categories'] ?? null) ? $lighthouse['categories'] : [];
        $audits = is_array($lighthouse['audits'] ?? null) ? $lighthouse['audits'] : [];

        return [
            'finalUrl' => $lighthouse['finalDisplayedUrl'] ?? ($raw['id'] ?? null),
            'scores' => [
                'performance' => $this->scoreOf($categories['performance'] ?? null),
                'accessibility' => $this->scoreOf($categories['accessibility'] ?? null),
                'bestPractices' => $this->scoreOf($categories['best-practices'] ?? null),
                'seo' => $this->scoreOf($categories['seo'] ?? null),
            ],
            'lab' => $this->labMetrics($audits),
            'field' => $this->fieldMetrics($raw),
            'opportunities' => $this->opportunities($audits, $ownHost),
        ];
    }

    /**
     * @param array<string, mixed> $audits
     *
     * @return array<string, mixed>
     */
    private function labMetrics(array $audits): array
    {
        return [
            'lcpMs' => $this->numeric($audits['largest-contentful-paint'] ?? null),
            'tbtMs' => $this->numeric($audits['total-blocking-time'] ?? null),
            'cls' => $this->numericFloat($audits['cumulative-layout-shift'] ?? null),
            'fcpMs' => $this->numeric($audits['first-contentful-paint'] ?? null),
            'speedIndexMs' => $this->numeric($audits['speed-index'] ?? null),
            'ttiMs' => $this->numeric($audits['interactive'] ?? null),
            'display' => [
                'lcp' => $this->display($audits['largest-contentful-paint'] ?? null),
                'tbt' => $this->display($audits['total-blocking-time'] ?? null),
                'cls' => $this->display($audits['cumulative-layout-shift'] ?? null),
                'fcp' => $this->display($audits['first-contentful-paint'] ?? null),
                'speedIndex' => $this->display($audits['speed-index'] ?? null),
                'tti' => $this->display($audits['interactive'] ?? null),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>|null
     */
    private function fieldMetrics(array $raw): ?array
    {
        $experience = is_array($raw['loadingExperience'] ?? null) ? $raw['loadingExperience'] : null;
        $source = 'page';
        if (null === $experience || empty($experience['metrics'])) {
            $experience = is_array($raw['originLoadingExperience'] ?? null) ? $raw['originLoadingExperience'] : null;
            $source = 'origin';
        }

        if (null === $experience || empty($experience['metrics']) || !is_array($experience['metrics'])) {
            return null;
        }

        $metrics = $experience['metrics'];

        return [
            'source' => $source,
            'overall' => $experience['overall_category'] ?? null,
            'lcp' => $this->fieldMetric($metrics['LARGEST_CONTENTFUL_PAINT_MS'] ?? null),
            'inp' => $this->fieldMetric($metrics['INTERACTION_TO_NEXT_PAINT'] ?? null),
            'cls' => $this->fieldMetric($metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE'] ?? null),
            'fcp' => $this->fieldMetric($metrics['FIRST_CONTENTFUL_PAINT_MS'] ?? null),
        ];
    }

    /**
     * @param mixed $metric
     *
     * @return array{percentile: int|null, rating: string|null}|null
     */
    private function fieldMetric(mixed $metric): ?array
    {
        if (!is_array($metric)) {
            return null;
        }

        return [
            'percentile' => isset($metric['percentile']) ? (int) $metric['percentile'] : null,
            'rating' => isset($metric['category']) ? (string) $metric['category'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $audits
     *
     * @return array<int, array<string, mixed>>
     */
    private function opportunities(array $audits, ?string $ownHost): array
    {
        $out = [];
        foreach (self::ACTIONABLE_AUDITS as $id) {
            $audit = $audits[$id] ?? null;
            if (!is_array($audit)) {
                continue;
            }

            $score = $this->rawScore($audit);
            $savingsMs = $this->savingsMs($audit);
            // Skip audits that already pass and bring nothing to fix.
            if (null !== $score && $score >= 0.9 && $savingsMs <= 0) {
                continue;
            }

            $items = $this->auditItems($audit, $ownHost);
            $out[] = [
                'id' => $id,
                'title' => (string) ($audit['title'] ?? $id),
                'description' => $this->plainText((string) ($audit['description'] ?? '')),
                'displayValue' => isset($audit['displayValue']) ? (string) $audit['displayValue'] : null,
                'score' => null === $score ? null : (int) round($score * 100),
                'severity' => $this->severity($score),
                'savingsMs' => $savingsMs > 0 ? $savingsMs : null,
                'items' => $items,
            ];
        }

        usort($out, static fn (array $a, array $b): int => ($b['savingsMs'] ?? 0) <=> ($a['savingsMs'] ?? 0));

        return $out;
    }

    /**
     * @param array<string, mixed> $audit
     *
     * @return array<int, array{type: string, label: string, detail: string|null}>
     */
    private function auditItems(array $audit, ?string $ownHost): array
    {
        $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
        $rows = is_array($details['items'] ?? null) ? $details['items'] : [];

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $url = isset($row['url']) && is_string($row['url']) ? $row['url'] : null;
            if (null === $url) {
                $entity = $row['entity'] ?? null;
                if (is_string($entity) && '' !== $entity) {
                    $items[] = ['type' => 'third-party', 'label' => $entity, 'detail' => $this->itemDetail($row)];
                }
                continue;
            }

            $source = $this->sourceMapper->describe($url, $ownHost);
            $items[] = [
                'type' => $source['type'],
                'label' => $source['label'],
                'detail' => $this->itemDetail($row),
            ];

            if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                break;
            }
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function itemDetail(array $row): ?string
    {
        $parts = [];
        if (isset($row['wastedBytes']) && is_numeric($row['wastedBytes']) && $row['wastedBytes'] > 0) {
            $parts[] = $this->kb((int) $row['wastedBytes']).' à économiser';
        } elseif (isset($row['totalBytes']) && is_numeric($row['totalBytes']) && $row['totalBytes'] > 0) {
            $parts[] = $this->kb((int) $row['totalBytes']);
        }
        if (isset($row['wastedMs']) && is_numeric($row['wastedMs']) && $row['wastedMs'] > 0) {
            $parts[] = (int) round((float) $row['wastedMs']).' ms';
        } elseif (isset($row['blockingTime']) && is_numeric($row['blockingTime']) && $row['blockingTime'] > 0) {
            $parts[] = (int) round((float) $row['blockingTime']).' ms bloquants';
        } elseif (isset($row['mainThreadTime']) && is_numeric($row['mainThreadTime']) && $row['mainThreadTime'] > 0) {
            $parts[] = (int) round((float) $row['mainThreadTime']).' ms (thread principal)';
        }

        return [] === $parts ? null : implode(' · ', $parts);
    }

    private function severity(?float $score): string
    {
        if (null === $score) {
            return 'low';
        }
        if ($score < 0.5) {
            return 'high';
        }
        if ($score < 0.9) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param mixed $category
     */
    private function scoreOf(mixed $category): ?int
    {
        if (!is_array($category) || !isset($category['score']) || !is_numeric($category['score'])) {
            return null;
        }

        return (int) round((float) $category['score'] * 100);
    }

    /**
     * @param array<string, mixed> $audit
     */
    private function rawScore(array $audit): ?float
    {
        return isset($audit['score']) && is_numeric($audit['score']) ? (float) $audit['score'] : null;
    }

    /**
     * @param array<string, mixed> $audit
     */
    private function savingsMs(array $audit): int
    {
        $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
        if (isset($details['overallSavingsMs']) && is_numeric($details['overallSavingsMs'])) {
            return (int) round((float) $details['overallSavingsMs']);
        }

        return 0;
    }

    /**
     * @param mixed $audit
     */
    private function numeric(mixed $audit): ?int
    {
        if (!is_array($audit) || !isset($audit['numericValue']) || !is_numeric($audit['numericValue'])) {
            return null;
        }

        return (int) round((float) $audit['numericValue']);
    }

    /**
     * @param mixed $audit
     */
    private function numericFloat(mixed $audit): ?float
    {
        if (!is_array($audit) || !isset($audit['numericValue']) || !is_numeric($audit['numericValue'])) {
            return null;
        }

        return round((float) $audit['numericValue'], 3);
    }

    /**
     * @param mixed $audit
     */
    private function display(mixed $audit): ?string
    {
        return is_array($audit) && isset($audit['displayValue']) ? (string) $audit['displayValue'] : null;
    }

    private function plainText(string $markdown): string
    {
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $markdown) ?? $markdown;

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function kb(int $bytes): string
    {
        return (int) round($bytes / 1024).' Ko';
    }
}
