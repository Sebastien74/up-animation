<?php

declare(strict_types=1);

namespace App\Service\Admin;

/**
 * Render a stored page-analysis report as a self-contained Markdown document, so an
 * admin can copy it and hand it over for review. Only actionable findings (high /
 * medium / low) are emitted; informational and compliant checks are skipped.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageAnalysisMarkdownFormatter
{
    private const array SEVERITY_LABEL = ['high' => 'Critique', 'medium' => 'À surveiller', 'low' => 'Mineur'];

    /**
     * @param array<string, mixed> $report
     */
    public function format(array $report, string $pageCode, ?string $name = null): string
    {
        $meta = $report['meta'] ?? [];
        $summary = $report['summary'] ?? [];

        $heading = '/'.ltrim($pageCode, '/');
        if (null !== $name && '' !== $name) {
            $heading .= ' — '.$name;
        }

        $lines = ['# Analyse de page '.$heading, ''];
        $score = $report['score'] ?? null;
        $lines[] = '- Indice : '.(null === $score ? 'N/A' : $score.'/100');
        if (isset($meta['httpStatus'])) {
            $lines[] = '- Statut HTTP : '.$meta['httpStatus'];
        }
        $lines[] = '- Poids HTML : '.($meta['kb'] ?? 0).' Ko';
        $lines[] = '- Requêtes : '.($meta['requests'] ?? 0);
        $lines[] = '- DOM : '.($meta['dom'] ?? 0).' éléments';
        $lines[] = '- Problèmes : '.($summary['high'] ?? 0).' critiques · '.($summary['medium'] ?? 0).' à surveiller · '.($summary['low'] ?? 0).' mineurs';
        $lines[] = '';

        foreach ($report['groups'] ?? [] as $group) {
            $problems = array_values(array_filter(
                $group['findings'] ?? [],
                static fn (array $finding): bool => isset(self::SEVERITY_LABEL[$finding['severity'] ?? ''])
            ));
            if ([] === $problems) {
                continue;
            }

            $lines[] = '## '.($group['label'] ?? '');
            $lines[] = '';
            foreach ($problems as $finding) {
                $value = (string) ($finding['value'] ?? '');
                $lines[] = '### ['.self::SEVERITY_LABEL[$finding['severity']].'] '.($finding['label'] ?? '').('' !== $value ? ' — '.$value : '');
                if (!empty($finding['reco'])) {
                    $lines[] = (string) $finding['reco'];
                }
                $breakdown = $finding['breakdown'] ?? [];
                $samples = $finding['samples'] ?? [];
                if ([] !== $breakdown) {
                    $affected = (int) ($finding['affected'] ?? 0);
                    $lines[] = '';
                    $lines[] = 'Répartition ('.$affected.' au total, '.count($breakdown).' type(s)) :';
                    foreach ($breakdown as $group) {
                        $paths = $group['paths'] ?? [];
                        $lines[] = '- `'.$group['signature'].'` ×'.$group['count'];
                        foreach ($paths as $path) {
                            $lines[] = '  - '.$path;
                        }
                        $extra = (int) $group['count'] - count($paths);
                        if ($extra > 0) {
                            $lines[] = '  - +'.$extra.' autres';
                        }
                    }
                } elseif ([] !== $samples) {
                    $affected = (int) ($finding['affected'] ?? count($samples));
                    $lines[] = '';
                    $lines[] = 'Éléments concernés ('.$affected.') :';
                    foreach ($samples as $sample) {
                        $lines[] = '- `'.$sample.'`';
                    }
                    $extra = $affected - count($samples);
                    if ($extra > 0) {
                        $lines[] = '- +'.$extra.' autres';
                    }
                }
                $lines[] = '';
            }
        }

        return rtrim(implode("\n", $lines))."\n";
    }
}
