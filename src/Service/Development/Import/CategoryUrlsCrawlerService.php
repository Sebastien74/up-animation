<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * CategoryUrlsCrawlerService.
 *
 * Crawl category pages and detect which product URLs are referenced inside.
 * It does NOT scrape "contents" blocks: it only builds a list of matching
 * product URLs and stores them under categories[<categoryUrl>]["urls"] in contents.json.
 *
 * IMPORTANT RULE (requested):
 * - For category pages, product links must be extracted ONLY from inside <article> elements.
 *   Example: <article ...><a href="...product...">Read More</a></article>
 *
 * Pagination:
 * - /category/<slug>/page/<n> pages are crawled too and merged into the base category.
 * - Pagination links are usually outside <article>, so they are detected from the full DOM.
 *
 * @author Sébastien FOURNIER
 */
readonly class CategoryUrlsCrawlerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Extract product URLs referenced in a category page (including pagination).
     *
     * @param string              $categoryUrl
     * @param array<int, string>  $productUrls
     * @param int                 $timeout
     * @param string              $userAgent
     *
     * @return array<int, string> Canonical product URLs (unique, sorted)
     */
    public function extractCategoryProductUrls(string $categoryUrl, array $productUrls, int $timeout, string $userAgent): array
    {
        $categoryUrl = trim($categoryUrl);
        if ($categoryUrl === '' || $productUrls === []) {
            return [];
        }

        // Base category URL (pagination collapsed)
        $baseCategoryUrl = $this->normalizeCategoryPaginationUrl($categoryUrl);
        $baseNormalized = $this->normalizeUrl($baseCategoryUrl);

        // Map normalized product URL => canonical product URL
        $productIndex = [];
        foreach ($productUrls as $pUrl) {
            if (!is_string($pUrl) || trim($pUrl) === '') {
                continue;
            }
            $canonical = trim($pUrl);
            $normalized = $this->normalizeUrl($canonical);
            if ($normalized !== '') {
                $productIndex[$normalized] = $canonical;
            }
        }

        if ($productIndex === []) {
            return [];
        }

        $foundProducts = [];
        $queue = [$baseCategoryUrl];
        $visited = [];

        // Hard safety limit to prevent infinite loops
        $maxPages = 50;

        while ($queue !== [] && count($visited) < $maxPages) {
            $current = array_shift($queue);
            if (!is_string($current) || trim($current) === '') {
                continue;
            }

            $current = trim($current);
            $currentNorm = $this->normalizeUrl($current);
            if ($currentNorm === '' || isset($visited[$currentNorm])) {
                continue;
            }
            $visited[$currentNorm] = true;

            $html = $this->fetchHtml($current, $timeout, $userAgent);
            if ($html === '') {
                continue;
            }

            $crawler = new Crawler($html, $current);

            /**
             * 1) PRODUCT LINKS: ONLY inside <article>
             */
            foreach ($crawler->filter('article a[href]') as $a) {
                if (!$a instanceof \DOMElement) {
                    continue;
                }

                $href = trim((string) $a->getAttribute('href'));
                if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                    continue;
                }

                $abs = $this->resolveUrl($current, $href);
                if ($abs === '') {
                    continue;
                }

                $normalized = $this->normalizeUrl($abs);
                if ($normalized === '') {
                    continue;
                }

                if (isset($productIndex[$normalized])) {
                    $foundProducts[$productIndex[$normalized]] = true;
                }
            }

            /**
             * 2) PAGINATION LINKS: detected from full DOM (often outside <article>)
             */
            foreach ($crawler->filter('a[href]') as $a) {
                if (!$a instanceof \DOMElement) {
                    continue;
                }

                $href = trim((string) $a->getAttribute('href'));
                if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                    continue;
                }

                $abs = $this->resolveUrl($current, $href);
                if ($abs === '') {
                    continue;
                }

                $normalized = $this->normalizeUrl($abs);
                if ($normalized === '') {
                    continue;
                }

                if ($this->isPaginationUrlForCategory($normalized, $baseNormalized)) {
                    $queue[] = $abs;
                }
            }
        }

        $out = array_keys($foundProducts);
        sort($out);

        return $out;
    }

    /**
     * Fetch category HTML.
     */
    private function fetchHtml(string $url, int $timeout, string $userAgent): string
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml',
                ],
                'max_redirects' => 5,
                'timeout' => $timeout,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 400) {
                return '';
            }

            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? '';
            if (!is_string($contentType) || stripos($contentType, 'text/html') === false) {
                return '';
            }

            $html = $response->getContent(false);

            return is_string($html) ? $html : '';
        } catch (TransportExceptionInterface) {
            return '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Resolve relative/absolute href into an absolute URL (best-effort).
     */
    private function resolveUrl(string $baseUrl, string $href): string
    {
        $href = trim($href);
        if ($href === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $href) === 1) {
            return $href;
        }

        if (!str_starts_with($href, '/')) {
            return '';
        }

        $parts = parse_url($baseUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        return sprintf('%s://%s%s', $parts['scheme'], $parts['host'], $href);
    }

    /**
     * Normalize URL for matching: drop query/fragment, trim trailing slash.
     */
    private function normalizeUrl(string $url): string
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

        return sprintf('%s://%s%s', $scheme, $host, $path);
    }

    /**
     * Normalize category URL by removing trailing /page/<n> when present.
     */
    private function normalizeCategoryPaginationUrl(string $url): string
    {
        $url = $this->normalizeUrl($url);
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

        $path = preg_replace('~^(/category/[^/]+)(?:/page/\d+)$~i', '$1', $path) ?? $path;

        return sprintf('%s://%s%s', $scheme, $host, $path);
    }

    /**
     * Check if a URL is a pagination page for a base category.
     *
     * @param string $normalizedUrl  Normalized url being checked
     * @param string $baseNormalized Normalized base category url
     */
    private function isPaginationUrlForCategory(string $normalizedUrl, string $baseNormalized): bool
    {
        if ($normalizedUrl === '' || $baseNormalized === '') {
            return false;
        }

        $parts = parse_url($normalizedUrl);
        if (!is_array($parts)) {
            return false;
        }

        $path = (string) ($parts['path'] ?? '');
        $path = rtrim($path, '/');

        $baseParts = parse_url($baseNormalized);
        if (!is_array($baseParts)) {
            return false;
        }

        $basePath = (string) ($baseParts['path'] ?? '');
        $basePath = rtrim($basePath, '/');

        return preg_match('~^' . preg_quote($basePath, '~') . '/page/\d+$~i', $path) === 1;
    }
}
