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
     * @param string|null $ownHost the page's own host, to exclude it from the third-party
     *                             domains count (falls back to canonical/og:url if null)
     *
     * @return array{meta: array<string, mixed>, score: int|null, summary: array<string, int>, groups: array<int, array<string, mixed>>}
     */
    public function analyze(string $html, ?string $urlCode = null, ?string $ownHost = null): array;
}
