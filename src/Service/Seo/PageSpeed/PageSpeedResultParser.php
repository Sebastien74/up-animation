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
    private const int MAX_ITEMS_PER_AUDIT = 25;

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
        $headings = is_array($details['headings'] ?? null) ? $details['headings'] : [];
        $rows = is_array($details['items'] ?? null) ? $details['items'] : [];

        $items = [];
        $this->collectRows($items, $rows, $headings, $ownHost);

        return $items;
    }

    /**
     * Headings-driven extraction: every column the audit declares (key, label, valueType)
     * is emitted, so the report keeps the same information as pagespeed.web.dev. "Insight"
     * audits nest sub-tables (each with their own headings): they are flattened recursively.
     *
     * @param array<int, array{type: string, label: string, detail: string|null}> $items
     * @param array<int, mixed>                                                    $rows
     * @param array<int, mixed>                                                    $headings
     */
    private function collectRows(array &$items, array $rows, array $headings, ?string $ownHost): void
    {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            // Insight "list" audits wrap each block in a titled/described list-section whose
            // payload (a table or a critical-request tree) sits under "value".
            if ('list-section' === ($row['type'] ?? null)) {
                $this->collectSection($items, $row, $ownHost);
                if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                    return;
                }
                continue;
            }

            if (isset($row['items']) && is_array($row['items'])) {
                $childHeadings = is_array($row['headings'] ?? null) ? $row['headings'] : $headings;
                $this->collectRows($items, $row['items'], $childHeadings, $ownHost);
                if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                    return;
                }
                continue;
            }

            $item = $this->rowToItem($row, $headings, $ownHost);
            if (null !== $item) {
                $items[] = $item;
            }
            if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                return;
            }
        }
    }

    /**
     * A list-section: emit its title/description header, then its payload (nested table or
     * critical-request tree).
     *
     * @param array<int, array{type: string, label: string, detail: string|null}> $items
     * @param array<string, mixed>                                                 $section
     */
    private function collectSection(array &$items, array $section, ?string $ownHost): void
    {
        $title = is_string($section['title'] ?? null) ? trim($section['title']) : null;
        $description = is_string($section['description'] ?? null) ? $this->plainText($section['description']) : null;
        if (null !== $title || null !== $description) {
            $items[] = ['type' => 'other', 'label' => $title ?? '—', 'detail' => '' !== (string) $description ? $description : null, 'image' => null];
            if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                return;
            }
        }

        $value = is_array($section['value'] ?? null) ? $section['value'] : [];
        if ('network-tree' === ($value['type'] ?? null)) {
            $longest = is_array($value['longestChain'] ?? null) && is_numeric($value['longestChain']['duration'] ?? null)
                ? (int) round((float) $value['longestChain']['duration']) : null;
            if (null !== $longest) {
                $items[] = ['type' => 'other', 'label' => 'Latence maximale du chemin critique', 'detail' => $longest.' ms', 'image' => null];
            }
            $this->collectChain($items, is_array($value['chains'] ?? null) ? $value['chains'] : [], $ownHost);

            return;
        }

        $headings = is_array($value['headings'] ?? null) ? $value['headings'] : [];
        $rows = is_array($value['items'] ?? null) ? $value['items'] : [];
        if ([] !== $rows) {
            $this->collectRows($items, $rows, $headings, $ownHost);
        }
    }

    /**
     * Walk a critical-request chain tree, emitting each request with its time and weight.
     *
     * @param array<int, array{type: string, label: string, detail: string|null}> $items
     * @param array<string, mixed>                                                 $chains
     */
    private function collectChain(array &$items, array $chains, ?string $ownHost): void
    {
        foreach ($chains as $node) {
            if (!is_array($node)) {
                continue;
            }
            if (is_string($node['url'] ?? null)) {
                $source = $this->sourceMapper->describe($node['url'], $ownHost);
                $parts = [];
                if (is_numeric($node['navStartToEndTime'] ?? null)) {
                    $parts[] = (int) round((float) $node['navStartToEndTime']).' ms';
                }
                if (is_numeric($node['transferSize'] ?? null) && $node['transferSize'] > 0) {
                    $parts[] = $this->kb((int) $node['transferSize']);
                }
                $items[] = ['type' => $source['type'], 'label' => $source['label'], 'detail' => [] === $parts ? null : implode(' · ', $parts), 'image' => $this->imageUrl($node['url'])];
            }
            if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                return;
            }
            if (is_array($node['children'] ?? null)) {
                $this->collectChain($items, $node['children'], $ownHost);
                if (count($items) >= self::MAX_ITEMS_PER_AUDIT) {
                    return;
                }
            }
        }
    }

    /**
     * One detail row -> displayable item: the first column becomes the label, every other
     * column (and its subItems) becomes "Label : value" formatted by its valueType.
     *
     * @param array<string, mixed> $row
     * @param array<int, mixed>    $headings
     *
     * @return array{type: string, label: string, detail: string|null, image: string|null}|null
     */
    private function rowToItem(array $row, array $headings, ?string $ownHost): ?array
    {
        $label = null;
        $type = 'other';
        $image = null;
        $parts = [];

        foreach ($headings as $heading) {
            if (!is_array($heading)) {
                continue;
            }
            $key = isset($heading['key']) && is_string($heading['key']) ? $heading['key'] : null;
            $valueType = isset($heading['valueType']) ? (string) $heading['valueType'] : 'text';
            $colLabel = isset($heading['label']) ? trim((string) $heading['label']) : '';

            $value = null === $key ? null : $this->formatCell($row[$key] ?? null, $valueType, $ownHost);
            if (null !== $value && '' !== $value) {
                if (null === $label) {
                    $label = $value;
                    $type = $this->cellType($valueType, null === $key ? null : ($row[$key] ?? null), $ownHost);
                    if ('url' === $valueType && is_string($row[$key] ?? null)) {
                        $image = $this->imageUrl($row[$key]);
                    }
                } elseif (!in_array($value, $parts, true)) {
                    $parts[] = ('' !== $colLabel ? $colLabel.' : ' : '').$value;
                }
            }

            foreach ($this->subItemValues($row, $heading, $ownHost) as $subValue) {
                if ($subValue !== $label && !in_array($subValue, $parts, true)) {
                    $parts[] = $subValue;
                }
            }
        }

        if (null === $label && [] === $parts) {
            return null;
        }

        return [
            'type' => $type,
            'label' => $label ?? '—',
            'detail' => [] === $parts ? null : implode(' · ', $parts),
            'image' => $image,
        ];
    }

    /**
     * Format a single cell value according to its Lighthouse valueType.
     */
    private function formatCell(mixed $value, string $valueType, ?string $ownHost): ?string
    {
        return match ($valueType) {
            'bytes' => is_numeric($value) && (float) $value > 0 ? $this->kb((int) $value) : null,
            'ms', 'timespanMs' => is_numeric($value) && (float) $value > 0 ? (int) round((float) $value).' ms' : null,
            'numeric' => $this->numericText($value),
            'url' => is_string($value) && '' !== $value ? $this->sourceMapper->describe($value, $ownHost)['label'] : null,
            'node' => $this->nodeText($value),
            'source-location' => $this->sourceLocationText($value, $ownHost),
            'link' => $this->linkText($value),
            default => $this->textCell($value),
        };
    }

    private function cellType(string $valueType, mixed $value, ?string $ownHost): string
    {
        return match ($valueType) {
            'url' => is_string($value) ? $this->sourceMapper->describe($value, $ownHost)['type'] : 'other',
            'node' => 'node',
            'source-location' => is_array($value) && is_string($value['url'] ?? null) ? $this->sourceMapper->describe($value['url'], $ownHost)['type'] : 'source',
            default => 'other',
        };
    }

    /**
     * subItems of a row formatted through the column's subItemsHeading (related node,
     * layout-shift cause, per-origin values…).
     *
     * @param array<string, mixed> $row
     * @param array<string, mixed> $heading
     *
     * @return array<int, string>
     */
    private function subItemValues(array $row, array $heading, ?string $ownHost): array
    {
        $sub = $row['subItems'] ?? null;
        $rows = is_array($sub) && is_array($sub['items'] ?? null) ? $sub['items'] : [];
        $si = is_array($heading['subItemsHeading'] ?? null) ? $heading['subItemsHeading'] : null;
        $key = is_array($si) && is_string($si['key'] ?? null) ? $si['key'] : null;
        // Skip subItems that just repeat the parent column metric (e.g. wastedBytes/wastedBytes).
        if (null === $key || [] === $rows || $key === ($heading['key'] ?? null)) {
            return [];
        }
        $valueType = isset($si['valueType']) ? (string) $si['valueType'] : (string) ($heading['valueType'] ?? 'text');

        $out = [];
        foreach ($rows as $item) {
            if (!is_array($item)) {
                continue;
            }
            $value = $this->formatCell($item[$key] ?? null, $valueType, $ownHost);
            if (null !== $value && '' !== $value && !in_array($value, $out, true)) {
                $out[] = $value;
            }
            if (count($out) >= 3) {
                break;
            }
        }

        return $out;
    }

    private function numericText(mixed $value): ?string
    {
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }
        if (!is_numeric($value)) {
            return null;
        }
        $float = (float) $value;

        return $float === (float) (int) $float ? (string) (int) $float : rtrim(rtrim(sprintf('%.3f', $float), '0'), '.');
    }

    private function nodeText(mixed $node): ?string
    {
        if (!is_array($node)) {
            return null;
        }
        foreach (['selector', 'snippet', 'nodeLabel', 'value'] as $key) {
            if (isset($node[$key]) && is_string($node[$key]) && '' !== trim($node[$key])) {
                return $this->truncate(trim($node[$key]), 160);
            }
        }

        return null;
    }

    private function sourceLocationText(mixed $source, ?string $ownHost): ?string
    {
        if (!is_array($source)) {
            return null;
        }
        if (is_string($source['url'] ?? null)) {
            $line = isset($source['line']) && is_numeric($source['line']) ? ':'.(int) $source['line'] : '';

            return $this->sourceMapper->describe($source['url'], $ownHost)['label'].$line;
        }
        if (is_string($source['value'] ?? null) && '' !== trim($source['value'])) {
            return $this->truncate(trim($source['value']), 160);
        }

        return null;
    }

    private function linkText(mixed $value): ?string
    {
        if (is_array($value)) {
            foreach (['text', 'url'] as $key) {
                if (is_string($value[$key] ?? null) && '' !== trim($value[$key])) {
                    return $this->truncate(trim($value[$key]), 160);
                }
            }

            return null;
        }

        return $this->textCell($value);
    }

    private function textCell(mixed $value): ?string
    {
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        $text = $this->plainText((string) $value);

        return '' === $text ? null : $this->truncate($text, 500);
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
