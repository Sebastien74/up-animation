<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

/**
 * UrlContentsClassifierService.
 *
 * Classify URLs into buckets (products / indexes / categories / pages) and provide JSON helpers
 * used by crawl commands.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UrlContentsClassifierService
{
    /**
     * Read an existing contents.json map.
     *
     * Used to preserve already extracted "contents" blocks when regenerating the map.
     * Returns an empty array if the file does not exist, is empty, or is invalid JSON.
     *
     * @param string $path
     *
     * @return array<string, mixed>
     */
    public function readContentsMapFromJson(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Read a JSON file and return URLs list.
     * Supports both formats:
     * - payload with "urls" key (recommended)
     * - raw list of URLs
     *
     * @param string $inputPath
     * @param int    $limit
     *
     * @return array<int, string>
     */
    public function readUrlsFromJson(string $inputPath, int $limit): array
    {
        $raw = @file_get_contents($inputPath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $urls = [];
        if (is_array($decoded) && isset($decoded['urls']) && is_array($decoded['urls'])) {
            $urls = $decoded['urls'];
        } elseif (is_array($decoded)) {
            $urls = $decoded;
        }

        $urls = array_values(array_unique(array_filter($urls, static fn ($u) => is_string($u) && trim($u) !== '')));

        if (count($urls) > $limit) {
            $urls = array_slice($urls, 0, $limit);
        }

        return $urls;
    }

    /**
     * Classify URLs into products/indexes/categories/pages based on their path.
     *
     * @param array<int, string> $urls
     *
     * @param array<string, mixed> $existingMap
     *
     * @return array<string, array<string, array{contents: mixed}>>
     */
    public function classify(array $urls, array $existingMap = []): array
    {
        $out = [
            'products' => [],
            'indexes' => [],
            'categories' => [],
            'pages' => [],
        ];

        foreach ($urls as $url) {
            if (!is_string($url) || trim($url) === '') {
                continue;
            }

            $url = trim($url);
            $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');

            $bucket = $this->bucketFromPath($path);

            $existingContents = $existingMap[$bucket][$url]['contents'] ?? null;

            // If an existing non-empty contents array is present, preserve it.
            if (is_array($existingContents) && $existingContents !== []) {
                $out[$bucket][$url] = ['contents' => $existingContents];
                continue;
            }

            $out[$bucket][$url] = ['contents' => []];
        }

        // Stable output (useful for diffs)
        ksort($out['products']);
        ksort($out['indexes']);
        ksort($out['categories']);
        ksort($out['pages']);

        return $out;
    }

    /**
     * Decide which bucket a path belongs to.
     *
     * Rules (order matters):
     * 1) /les-defis-... OR /animation-... => products
     * 2) /animations/... => indexes
     * 3) /category/... => categories
     * 4) else => pages
     *
     * @param string $path
     *
     * @return 'products'|'indexes'|'categories'|'pages'
     */
    private function bucketFromPath(string $path): string
    {
        // Normalize path (avoid empty, ensure leading slash)
        $path = trim($path);
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        // products: /les-defis-... or /animation-...
        if (
            preg_match('~^/les-defis-[^/]*$~i', $path) === 1
            || preg_match('~^/animation-[^/]*$~i', $path) === 1
            || preg_match('~^/spectacle-[^/]*$~i', $path) === 1
            || preg_match('~^/magie-[^/]*$~i', $path) === 1
            || preg_match('~^/location-[^/]*$~i', $path) === 1
            || preg_match('~^/bulles[^/]*$~i', $path) === 1
            || preg_match('~^/casino[^/]*$~i', $path) === 1
            || preg_match('~^/close-up[^/]*$~i', $path) === 1
            || preg_match('~^/formule-1[^/]*$~i', $path) === 1
            || preg_match('~^/graf[^/]*$~i', $path) === 1
            || preg_match('~^/magic-academy[^/]*$~i', $path) === 1
            || preg_match('~^/magicien[^/]*$~i', $path) === 1
            || preg_match('~^/mascotte[^/]*$~i', $path) === 1
            || preg_match('~^/olympiades[^/]*$~i', $path) === 1
            || preg_match('~^/quizz[^/]*$~i', $path) === 1
            || preg_match('~^/atelier-cirque[^/]*$~i', $path) === 1
            || preg_match('~^/ballons-ballooner[^/]*$~i', $path) === 1
            || preg_match('~^/buggy-teleguides[^/]*$~i', $path) === 1
            || preg_match('~^/pere-noel[^/]*$~i', $path) === 1
            || preg_match('~^/theatre-deambulatoire[^/]*$~i', $path) === 1
        ) {
            return 'products';
        }

        // indexes: /animations/...
        if (preg_match('~^/animations/.*$~i', $path) === 1) {
            return 'indexes';
        }

        // categories: /category/...
        if (preg_match('~^/category/.*$~i', $path) === 1) {
            return 'categories';
        }

        return 'pages';
    }
}
