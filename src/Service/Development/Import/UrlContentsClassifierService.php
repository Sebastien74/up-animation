<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

/**
 * UrlContentsClassifierService.
 *
 * Classify URLs into buckets (products / indexes / categories / pages) and provide JSON helpers
 * used by crawl commands.
 *
 * Pagination rule:
 * - /category/foo/page/2 => /category/foo (same key)
 *
 * Existing data in contents.json must be preserved when rebuilding the map:
 * - keep "contents" if non-empty
 * - keep "urls" (categories) if present
 *
 * @author Sébastien FOURNIER
 */
class UrlContentsClassifierService
{
    /**
     * Read an existing contents.json map.
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
     * @param array<int, string>   $urls
     * @param array<string, mixed> $existingMap
     *
     * @return array<string, array<string, array<string, mixed>>>
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

            // Categories pagination must be merged into the base category URL:
            // /category/foo/page/2 => /category/foo
            $keyUrl = $bucket === 'categories'
                ? $this->normalizeCategoryPaginationUrl($url)
                : $url;

            $entry = ['contents' => []];

            // Preserve existing non-empty contents
            $existing = $this->findExistingEntry($existingMap, $bucket, $keyUrl, $url);
            if (is_array($existing)) {
                if (isset($existing['contents']) && is_array($existing['contents']) && $existing['contents'] !== []) {
                    $entry['contents'] = $existing['contents'];
                }

                // Preserve categories urls (matched products)
                if ($bucket === 'categories' && isset($existing['urls']) && is_array($existing['urls']) && $existing['urls'] !== []) {
                    $entry['urls'] = array_values(array_unique(array_filter($existing['urls'], 'is_string')));
                }
            }

            // Merge if the same key was already created (pagination collapse)
            if (!isset($out[$bucket][$keyUrl])) {
                $out[$bucket][$keyUrl] = $entry;
            } else {
                $out[$bucket][$keyUrl] = $this->mergeEntries($out[$bucket][$keyUrl], $entry);
            }
        }

        ksort($out['products']);
        ksort($out['indexes']);
        ksort($out['categories']);
        ksort($out['pages']);

        return $out;
    }

    /**
     * Decide which bucket a path belongs to.
     *
     * @return 'products'|'indexes'|'categories'|'pages'
     */
    private function bucketFromPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        // products
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

        // indexes
        if (preg_match('~^/animations/.*$~i', $path) === 1) {
            return 'indexes';
        }

        // categories
        if (preg_match('~^/category/.*$~i', $path) === 1) {
            return 'categories';
        }

        return 'pages';
    }

    /**
     * Normalize paginated category URLs into their base category URL.
     *
     * @example https://up-animations.fr/category/anniversaire/page/2 => https://up-animations.fr/category/anniversaire
     */
    private function normalizeCategoryPaginationUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return rtrim($url, '/');
        }

        $scheme = (string) $parts['scheme'];
        $host = (string) $parts['host'];
        $path = (string) ($parts['path'] ?? '');
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');

        // Strip '/page/<n>' at the end for categories
        $path = preg_replace('~^(/category/[^/]+)(?:/page/\d+)$~i', '$1', $path) ?? $path;

        return sprintf('%s://%s%s', $scheme, $host, $path);
    }

    /**
     * Find the best existing entry to preserve.
     *
     * @param array<string, mixed> $existingMap
     *
     * @return array<string, mixed>|null
     */
    private function findExistingEntry(array $existingMap, string $bucket, string $keyUrl, string $rawUrl): ?array
    {
        if (isset($existingMap[$bucket][$keyUrl]) && is_array($existingMap[$bucket][$keyUrl])) {
            return $existingMap[$bucket][$keyUrl];
        }

        if (isset($existingMap[$bucket][$rawUrl]) && is_array($existingMap[$bucket][$rawUrl])) {
            return $existingMap[$bucket][$rawUrl];
        }

        if ($bucket === 'categories') {
            $baseKey = $this->normalizeCategoryPaginationUrl($rawUrl);
            if ($baseKey !== '' && isset($existingMap[$bucket][$baseKey]) && is_array($existingMap[$bucket][$baseKey])) {
                return $existingMap[$bucket][$baseKey];
            }
        }

        return null;
    }

    /**
     * Merge two entries.
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     *
     * @return array<string, mixed>
     */
    private function mergeEntries(array $a, array $b): array
    {
        $aContents = isset($a['contents']) && is_array($a['contents']) ? $a['contents'] : [];
        $bContents = isset($b['contents']) && is_array($b['contents']) ? $b['contents'] : [];

        if (($aContents === [] || !isset($a['contents'])) && $bContents !== []) {
            $a['contents'] = $bContents;
        } elseif (!isset($a['contents'])) {
            $a['contents'] = $aContents;
        }

        $aUrls = isset($a['urls']) && is_array($a['urls']) ? $a['urls'] : [];
        $bUrls = isset($b['urls']) && is_array($b['urls']) ? $b['urls'] : [];

        if ($aUrls !== [] || $bUrls !== []) {
            $merged = array_values(array_unique(array_merge(
                array_values(array_filter($aUrls, 'is_string')),
                array_values(array_filter($bUrls, 'is_string')),
            )));
            $a['urls'] = $merged;
        }

        return $a;
    }
}
