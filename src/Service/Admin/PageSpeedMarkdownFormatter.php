<?php

declare(strict_types=1);

namespace App\Service\Admin;

/**
 * Render a stored PageSpeed Insights report as a self-contained Markdown document, so an
 * admin can copy it and hand it over for review. Per strategy (mobile / desktop) it emits
 * the category scores, lab Core Web Vitals and, for every category, the actionable audits
 * (to fix + diagnostics) with their mapped resources. Passing, manual and not-applicable
 * audits are skipped: the document is meant to list what needs attention.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedMarkdownFormatter
{
    private const array STRATEGY_LABEL = ['mobile' => 'Mobile', 'desktop' => 'Ordinateur'];

    private const array CATEGORY_LABEL = [
        'performance' => 'Performance',
        'accessibility' => 'Accessibilité',
        'bestPractices' => 'Bonnes pratiques',
        'seo' => 'SEO',
    ];

    private const array SEVERITY_LABEL = ['fail' => 'Critique', 'average' => 'À surveiller', 'diagnostic' => 'Diagnostic'];

    private const array LAB_LABEL = [
        'lcp' => 'LCP',
        'tbt' => 'TBT',
        'cls' => 'CLS',
        'fcp' => 'FCP',
        'speedIndex' => 'Speed Index',
        'tti' => 'TTI',
    ];

    /**
     * @param array<string, mixed> $report
     */
    public function format(array $report, string $pageCode, ?string $name = null, ?\DateTimeInterface $measuredAt = null): string
    {
        $heading = '/'.ltrim($pageCode, '/');
        if (null !== $name && '' !== $name) {
            $heading .= ' — '.$name;
        }

        $lines = ['# PageSpeed Insights '.$heading, ''];
        $measuredUrl = isset($report['url']) && is_string($report['url']) ? $report['url'] : null;
        if (null !== $measuredUrl) {
            $lines[] = 'URL mesurée : '.$measuredUrl;
        }
        if (null !== $measuredAt) {
            $lines[] = 'Mesuré le '.$measuredAt->format('d/m/Y H:i');
        }
        if (null !== $measuredUrl || null !== $measuredAt) {
            $lines[] = '';
        }

        $strategies = is_array($report['strategies'] ?? null) ? $report['strategies'] : [];
        foreach (self::STRATEGY_LABEL as $key => $label) {
            $strat = is_array($strategies[$key] ?? null) ? $strategies[$key] : null;
            if (null === $strat) {
                continue;
            }

            $lines[] = '## '.$label;
            $lines[] = '';
            $this->appendScores($lines, $strat);
            $this->appendWarnings($lines, $strat);
            $this->appendLab($lines, $strat);
            $this->appendCategories($lines, $strat);
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    /**
     * @param array<int, string>  $lines
     * @param array<string, mixed> $strat
     */
    private function appendScores(array &$lines, array $strat): void
    {
        $scores = is_array($strat['scores'] ?? null) ? $strat['scores'] : [];
        foreach (self::CATEGORY_LABEL as $key => $label) {
            $score = $scores[$key] ?? null;
            $lines[] = '- '.$label.' : '.(null === $score ? 'N/A' : $score.'/100');
        }
        $lines[] = '';
    }

    /**
     * @param array<int, string>  $lines
     * @param array<string, mixed> $strat
     */
    private function appendWarnings(array &$lines, array $strat): void
    {
        $warnings = is_array($strat['warnings'] ?? null) ? $strat['warnings'] : [];
        if ([] === $warnings) {
            return;
        }

        $lines[] = '> Avertissements de mesure :';
        foreach ($warnings as $warning) {
            $lines[] = '> - '.$warning;
        }
        $lines[] = '';
    }

    /**
     * @param array<int, string>  $lines
     * @param array<string, mixed> $strat
     */
    private function appendLab(array &$lines, array $strat): void
    {
        $lab = is_array($strat['lab'] ?? null) ? $strat['lab'] : [];
        $display = is_array($lab['display'] ?? null) ? $lab['display'] : [];

        $parts = [];
        foreach (self::LAB_LABEL as $key => $label) {
            if (!empty($display[$key])) {
                $parts[] = $label.' '.$display[$key];
            }
        }

        if ([] !== $parts) {
            $lines[] = 'Core Web Vitals (labo) : '.implode(' · ', $parts);
            $lines[] = '';
        }
    }

    /**
     * @param array<int, string>  $lines
     * @param array<string, mixed> $strat
     */
    private function appendCategories(array &$lines, array $strat): void
    {
        $categories = is_array($strat['categories'] ?? null) ? $strat['categories'] : [];
        foreach (self::CATEGORY_LABEL as $key => $label) {
            $category = is_array($categories[$key] ?? null) ? $categories[$key] : null;
            if (null === $category) {
                continue;
            }

            $audits = is_array($category['audits'] ?? null) ? $category['audits'] : [];
            $actionable = array_values(array_filter(
                $audits,
                static fn (array $audit): bool => isset(self::SEVERITY_LABEL[$audit['severity'] ?? ''])
            ));
            if ([] === $actionable) {
                continue;
            }

            $lines[] = '### '.$label;
            $lines[] = '';
            foreach ($actionable as $audit) {
                $this->appendAudit($lines, $audit);
            }
        }
    }

    /**
     * @param array<int, string>  $lines
     * @param array<string, mixed> $audit
     */
    private function appendAudit(array &$lines, array $audit): void
    {
        $severity = self::SEVERITY_LABEL[$audit['severity']];
        $title = (string) ($audit['title'] ?? '');
        $value = isset($audit['displayValue']) && '' !== (string) $audit['displayValue'] ? ' — '.$audit['displayValue'] : '';
        $savings = !empty($audit['savingsMs']) ? ' (~'.round(((float) $audit['savingsMs']) / 1000, 1).' s)' : '';

        $lines[] = '#### ['.$severity.'] '.$title.$value.$savings;
        if (!empty($audit['description'])) {
            $lines[] = (string) $audit['description'];
        }

        $advice = is_array($audit['advice'] ?? null) ? $audit['advice'] : [];
        foreach ($advice as $tip) {
            if (is_array($tip) && !empty($tip['advice'])) {
                $prefix = '' !== (string) ($tip['title'] ?? '') ? '_'.$tip['title'].'_ : ' : '';
                $lines[] = $prefix.$tip['advice'];
            }
        }

        if (!empty($audit['learnMoreUrl'])) {
            $lines[] = 'En savoir plus : '.$audit['learnMoreUrl'];
        }

        $items = is_array($audit['items'] ?? null) ? $audit['items'] : [];
        if ([] !== $items) {
            $lines[] = '';
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $detail = !empty($item['detail']) ? ' — '.$item['detail'] : '';
                $lines[] = '- `'.($item['label'] ?? '').'`'.$detail;
            }
        }

        $lines[] = '';
    }
}
