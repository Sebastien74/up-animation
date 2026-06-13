<?php

declare(strict_types=1);

namespace App\Service\Admin;

/**
 * PageAnalyzerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface PageAnalyzerInterface
{
    /**
     * Analyze a rendered front HTML page and return a performance/rendering report.
     *
     * @return array{meta: array<string, mixed>, score: int|null, findings: array<int, array<string, mixed>>}
     */
    public function analyze(string $html, ?string $urlCode = null): array;
}
