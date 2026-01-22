<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use Symfony\Component\Yaml\Yaml;

class UrlContentsClassifierService
{
    /**
     * Read a YAML file and return URLs list.
     * Supports both formats:
     * - payload with "urls" key (recommended)
     * - raw list of URLs
     *
     * @param string $inputPath
     * @param int    $limit
     *
     * @return array<int, string>
     */
    public function readUrlsFromYaml(string $inputPath, int $limit): array
    {
        $yaml = Yaml::parseFile($inputPath);

        $urls = [];
        if (is_array($yaml) && isset($yaml['urls']) && is_array($yaml['urls'])) {
            $urls = $yaml['urls'];
        } elseif (is_array($yaml)) {
            $urls = $yaml;
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
     * @return array<string, array<string, array{contents: mixed}>>
     */
    public function classify(array $urls): array
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

            // Keep URL as key, "contents" null for now
            $out[$bucket][$url] = [
                'contents' => null,
            ];
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
        if (preg_match('~^/les-defis-[^/]*$~i', $path) === 1 || preg_match('~^/animation-[^/]*$~i', $path) === 1) {
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
