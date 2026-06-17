<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

/**
 * PageSpeedResultParser.
 *
 * Normalizes a raw PageSpeed Insights (Lighthouse v5) payload into a compact,
 * storable structure: category scores, lab Core Web Vitals, CrUX field data and the
 * full per-category audit breakdown (Opportunities / Diagnostics / Passed), each audit
 * enriched with its code-source mapping. The goal is to surface the same information as
 * the public pagespeed.web.dev report, category by category.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedResultParser
{
    private const int MAX_ITEMS_PER_AUDIT = 12;

    /**
     * Lighthouse category id => the key used in the normalized report.
     */
    private const array CATEGORY_KEYS = [
        'performance' => 'performance',
        'accessibility' => 'accessibility',
        'best-practices' => 'bestPractices',
        'seo' => 'seo',
    ];

    /**
     * Audit groups not worth listing: lab metrics (already shown as Core Web Vitals)
     * and hidden helpers.
     */
    private const array SKIPPED_GROUPS = ['metrics', 'hidden'];

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
        $stackPacks = is_array($lighthouse['stackPacks'] ?? null) ? $lighthouse['stackPacks'] : [];
        $advice = $this->stackAdvice($stackPacks);

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
            'warnings' => $this->runWarnings($lighthouse),
            'categories' => $this->categories($categories, $audits, $ownHost, $advice),
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
            'ttfb' => $this->fieldMetric($metrics['EXPERIMENTAL_TIME_TO_FIRST_BYTE'] ?? null),
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
     * Per-category audit breakdown, mirroring the public PSI report: every audit listed
     * under the category (via its auditRefs), grouped client-side by severity.
     *
     * @param array<string, mixed> $categories
     * @param array<string, mixed> $audits
     *
     * @return array<string, mixed>
     */
    private function categories(array $categories, array $audits, ?string $ownHost, array $advice = []): array
    {
        $out = [];
        foreach (self::CATEGORY_KEYS as $lhKey => $key) {
            $category = is_array($categories[$lhKey] ?? null) ? $categories[$lhKey] : null;
            if (null === $category) {
                continue;
            }

            $auditRefs = is_array($category['auditRefs'] ?? null) ? $category['auditRefs'] : [];
            $entries = [];

            foreach ($auditRefs as $ref) {
                if (!is_array($ref)) {
                    continue;
                }
                $group = isset($ref['group']) ? (string) $ref['group'] : null;
                if (null !== $group && in_array($group, self::SKIPPED_GROUPS, true)) {
                    continue;
                }

                $id = isset($ref['id']) ? (string) $ref['id'] : '';
                $audit = is_array($audits[$id] ?? null) ? $audits[$id] : null;
                if ('' === $id || null === $audit) {
                    continue;
                }

                $entries[] = $this->auditEntry($id, $audit, $group, $ownHost, $advice[$id] ?? []);
            }

            // Failing first (by potential savings, then weight), then everything else.
            usort($entries, function (array $a, array $b): int {
                $rank = fn (string $sev): int => ['fail' => 0, 'average' => 1, 'diagnostic' => 2, 'pass' => 3, 'na' => 4, 'manual' => 5][$sev] ?? 6;
                $cmp = $rank($a['severity']) <=> $rank($b['severity']);
                if (0 !== $cmp) {
                    return $cmp;
                }

                return ($b['savingsMs'] ?? 0) <=> ($a['savingsMs'] ?? 0)
                    ?: ($b['weight'] ?? 0) <=> ($a['weight'] ?? 0);
            });

            $out[$key] = [
                'title' => (string) ($category['title'] ?? $lhKey),
                'score' => $this->scoreOf($category),
                'audits' => $entries,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $audit
     *
     * @return array<string, mixed>
     */
    private function auditEntry(string $id, array $audit, ?string $group, ?string $ownHost, array $advice = []): array
    {
        $score = $this->rawScore($audit);
        $mode = isset($audit['scoreDisplayMode']) ? (string) $audit['scoreDisplayMode'] : 'numeric';
        $savingsMs = $this->savingsMs($audit);
        $severity = $this->severity($score, $mode);
        $rawDescription = (string) ($audit['description'] ?? '');
        $actionable = in_array($severity, ['fail', 'average', 'diagnostic'], true);

        return [
            'id' => $id,
            'group' => $group,
            'title' => (string) ($audit['title'] ?? $id),
            'description' => $this->plainText($rawDescription),
            // Official "Learn more" documentation link kept from the description markdown.
            'learnMoreUrl' => $this->docUrl($rawDescription),
            'displayValue' => isset($audit['displayValue']) ? (string) $audit['displayValue'] : null,
            'score' => null === $score ? null : (int) round($score * 100),
            'severity' => $severity,
            'savingsMs' => $savingsMs > 0 ? $savingsMs : null,
            'weight' => isset($audit['weight']) && is_numeric($audit['weight']) ? (int) $audit['weight'] : 0,
            // Offending resources only matter where there is something to fix or diagnose;
            // passing, manual and not-applicable audits keep their title, score and
            // description but list no resources (Google reports none for them either).
            'items' => $actionable ? $this->auditItems($audit, $ownHost) : [],
            // Stack-specific remediation advice (WordPress, React…), when Lighthouse
            // detected a matching technology.
            'advice' => $actionable ? $advice : [],
        ];
    }

    /**
     * Build a map of "audit id => list of stack-specific remediation advice" from the
     * Lighthouse stack packs (framework/CMS tailored fix instructions).
     *
     * @param array<int, mixed> $stackPacks
     *
     * @return array<string, array<int, array{title: string, advice: string}>>
     */
    private function stackAdvice(array $stackPacks): array
    {
        $map = [];
        foreach ($stackPacks as $pack) {
            if (!is_array($pack)) {
                continue;
            }
            $title = (string) ($pack['title'] ?? '');
            $descriptions = is_array($pack['descriptions'] ?? null) ? $pack['descriptions'] : [];
            foreach ($descriptions as $auditId => $advice) {
                if (is_string($advice) && '' !== trim($advice)) {
                    $map[(string) $auditId][] = ['title' => $title, 'advice' => $this->plainText($advice)];
                }
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $lighthouse
     *
     * @return array<int, string>
     */
    private function runWarnings(array $lighthouse): array
    {
        $warnings = is_array($lighthouse['runWarnings'] ?? null) ? $lighthouse['runWarnings'] : [];
        $out = [];
        foreach ($warnings as $warning) {
            if (is_string($warning) && '' !== trim($warning)) {
                $out[] = $this->plainText($warning);
            }
        }

        return $out;
    }

    /**
     * The resource URL when it points at an image, so the view can show a preview.
     */
    private function imageUrl(string $url): ?string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');

        return 1 === preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $path) ? $url : null;
    }

    /**
     * First documentation URL found in a markdown description (the "Learn more" link).
     */
    private function docUrl(string $markdown): ?string
    {
        if (1 === preg_match('/\[[^\]]+\]\((https?:\/\/[^)\s]+)\)/', $markdown, $matches)) {
            return $matches[1];
        }

        return null;
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
            if (null !== $url) {
                $source = $this->sourceMapper->describe($url, $ownHost);
                $items[] = ['type' => $source['type'], 'label' => $source['label'], 'detail' => $this->itemDetail($row), 'image' => $this->imageUrl($url)];
            } elseif (null !== ($node = $this->nodeLabel($row))) {
                // Accessibility / SEO audits point at a DOM node rather than a URL.
                $items[] = ['type' => 'node', 'label' => $node, 'detail' => $this->itemDetail($row)];
            } else {
                $entity = $row['entity'] ?? null;
                if (is_string($entity) && '' !== $entity) {
                    $items[] = ['type' => 'third-party', 'label' => $entity, 'detail' => $this->itemDetail($row)];
                    continue;
                }
                $source = $this->scalarLabel($row);
                if (null !== $source) {
                    $items[] = ['type' => 'other', 'label' => $source, 'detail' => $this->itemDetail($row)];
                }
            }

            if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                break;
            }
        }

        return $items;
    }

    /**
     * Human label for a DOM-node based detail row (accessibility, SEO, best practices).
     *
     * @param array<string, mixed> $row
     */
    private function nodeLabel(array $row): ?string
    {
        $node = $row['node'] ?? null;
        if (!is_array($node)) {
            return null;
        }

        foreach (['selector', 'snippet', 'nodeLabel'] as $key) {
            if (isset($node[$key]) && is_string($node[$key]) && '' !== trim($node[$key])) {
                return $this->truncate(trim($node[$key]), 160);
            }
        }

        return null;
    }

    /**
     * Fallback label for a detail row that is neither a URL nor a node (e.g. a source
     * location or a plain string value).
     *
     * @param array<string, mixed> $row
     */
    private function scalarLabel(array $row): ?string
    {
        $source = $row['source'] ?? null;
        if (is_array($source) && isset($source['url']) && is_string($source['url'])) {
            return $this->truncate($source['url'], 160);
        }

        foreach (['label', 'name', 'statistic', 'source'] as $key) {
            if (isset($row[$key]) && is_string($row[$key]) && '' !== trim($row[$key])) {
                return $this->truncate(trim($row[$key]), 160);
            }
        }

        return null;
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

    /**
     * Severity bucket used to group audits in the view, following PSI's own logic:
     * manual checks, not-applicable and informative audits are neutral; scored audits
     * fail / need improvement / pass on the 0.5 and 0.9 thresholds.
     */
    private function severity(?float $score, string $mode): string
    {
        if ('manual' === $mode) {
            return 'manual';
        }
        if ('notApplicable' === $mode) {
            return 'na';
        }
        if ('informative' === $mode || null === $score) {
            return 'diagnostic';
        }
        if ($score >= 0.9) {
            return 'pass';
        }
        if ($score >= 0.5) {
            return 'average';
        }

        return 'fail';
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

    private function truncate(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max - 1).'…' : $value;
    }

    private function kb(int $bytes): string
    {
        return (int) round($bytes / 1024).' Ko';
    }
}
