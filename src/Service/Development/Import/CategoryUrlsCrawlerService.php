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
 * Matching strategy:
 * - Extract all <a href> links from the category page
 * - Resolve relative URLs against the category URL
 * - Normalize (strip query + fragment, trim trailing slash)
 * - If the normalized URL matches a normalized product URL from contents.json,
 *   keep the canonical product URL.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class CategoryUrlsCrawlerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Extract product URLs referenced in a category page.
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

        try {
            $response = $this->httpClient->request('GET', $categoryUrl, [
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml',
                ],
                'max_redirects' => 5,
                'timeout' => $timeout,
            ]);

            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 400) {
                return [];
            }

            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? '';
            if (!is_string($contentType) || stripos($contentType, 'text/html') === false) {
                return [];
            }

            $html = $response->getContent(false);
            if (!is_string($html) || $html === '') {
                return [];
            }

            $crawler = new Crawler($html, $categoryUrl);

            $found = [];
            foreach ($crawler->filter('a[href]') as $a) {
                if (!$a instanceof \DOMElement) {
                    continue;
                }

                $href = trim((string) $a->getAttribute('href'));
                if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                    continue;
                }

                $abs = $this->resolveUrl($categoryUrl, $href);
                if ($abs === '') {
                    continue;
                }

                $normalized = $this->normalizeUrl($abs);
                if ($normalized === '') {
                    continue;
                }

                if (isset($productIndex[$normalized])) {
                    $found[$productIndex[$normalized]] = true;
                }
            }

            $out = array_keys($found);
            sort($out);

            return $out;
        } catch (TransportExceptionInterface) {
            return [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Normalize URLs for matching.
     * - remove fragment
     * - remove query
     * - trim trailing slash
     *
     * @param string $url
     *
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // Remove fragment
        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $url = substr($url, 0, $hashPos);
        }

        // Remove query
        $qPos = strpos($url, '?');
        if ($qPos !== false) {
            $url = substr($url, 0, $qPos);
        }

        // Trim trailing slash (except scheme://host/)
        if (preg_match('~^https?://[^/]+/$~i', $url) !== 1) {
            $url = rtrim($url, '/');
        }

        return $url;
    }

    /**
     * Resolve relative URLs against a base absolute URL.
     *
     * @param string $baseUrl
     * @param string $href
     *
     * @return string
     */
    private function resolveUrl(string $baseUrl, string $href): string
    {
        $href = trim($href);
        if ($href === '') {
            return '';
        }

        // Already absolute
        if (preg_match('~^https?://~i', $href) === 1) {
            return $href;
        }

        $base = parse_url($baseUrl);
        if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
            return '';
        }

        $scheme = (string) $base['scheme'];
        $host = (string) $base['host'];
        $port = isset($base['port']) ? ':' . (int) $base['port'] : '';

        // Root-relative
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $port . $href;
        }

        // Relative to current path
        $basePath = (string) ($base['path'] ?? '/');
        if ($basePath === '' || $basePath[0] !== '/') {
            $basePath = '/' . ltrim($basePath, '/');
        }

        // If base is a file path, use its directory
        $dir = rtrim(str_contains($basePath, '/') ? substr($basePath, 0, (int) strrpos($basePath, '/') + 1) : '/', '/');
        $dir = $dir === '' ? '/' : $dir . '/';

        $path = $dir . $href;

        // Normalize dot segments
        $path = preg_replace('~/\./~', '/', $path) ?? $path;
        while (str_contains($path, '../')) {
            $path = preg_replace('~/(?!\.\.)[^/]+/\.\./~', '/', $path, 1) ?? $path;
            if (!str_contains($path, '../')) {
                break;
            }
        }

        return $scheme . '://' . $host . $port . $path;
    }
}
