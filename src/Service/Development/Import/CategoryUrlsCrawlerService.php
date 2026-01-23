<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * CategoryUrlsCrawlerService.
 *
 * Crawl listing pages (categories + indexes) and detect which product URLs are referenced inside.
 *
 * Output usage:
 * - categories[<categoryUrl>]["urls"] = [ ... product urls ... ]
 * - indexes[<indexUrl>]["urls"] = [ ... product urls ... ]
 *
 * Notes:
 * - This service only maps existing products -> listing pages. It never creates products.
 * - Matching is scheme-insensitive (http/https), query/fragment-insensitive, and trailing-slash-insensitive.
 *
 * Matching strategy:
 * 1) Build an index of known product URLs from contents.json (products bucket)
 * 2) Fetch listing HTML and extract candidate links (scoped depending on the listing type)
 * 3) Normalize candidates and keep those matching known products
 * 4) Crawl pagination pages when detected (/page/<n>) and merge results
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
     * Extract product URLs referenced in a CATEGORY page.
     *
     * Rule (as requested): only consider links located inside <article> elements.
     *
     * @param string             $categoryUrl
     * @param array<int, string> $productUrls
     * @param int                $timeout
     * @param string             $userAgent
     *
     * @return array<int, string> Canonical product URLs (unique, sorted)
     */
    public function extractCategoryProductUrls(string $categoryUrl, array $productUrls, int $timeout, string $userAgent): array
    {
        return $this->extractListingProductUrls(
            listingUrl: $categoryUrl,
            productUrls: $productUrls,
            timeout: $timeout,
            userAgent: $userAgent,
            listingKind: 'category'
        );
    }

    /**
     * Extract product URLs referenced in an INDEX page (/animations/...).
     *
     * Rules:
     * - If <article> exists, use it (same as categories)
     * - Else, use cards blocks inside .col-lg-4 (your provided HTML)
     * - Only keep URLs that match known products
     *
     * @param string             $indexUrl
     * @param array<int, string> $productUrls
     * @param int                $timeout
     * @param string             $userAgent
     *
     * @return array<int, string> Canonical product URLs (unique, sorted)
     */
    public function extractIndexProductUrls(string $indexUrl, array $productUrls, int $timeout, string $userAgent): array
    {
        return $this->extractListingProductUrls(
            listingUrl: $indexUrl,
            productUrls: $productUrls,
            timeout: $timeout,
            userAgent: $userAgent,
            listingKind: 'index'
        );
    }

    /**
     * Shared extraction pipeline for listing pages.
     *
     * @param string             $listingUrl
     * @param array<int, string> $productUrls
     * @param int                $timeout
     * @param string             $userAgent
     * @param string             $listingKind  "category"|"index"
     *
     * @return array<int, string>
     */
    private function extractListingProductUrls(string $listingUrl, array $productUrls, int $timeout, string $userAgent, string $listingKind): array
    {
        $listingUrl = trim($listingUrl);
        if ($listingUrl === '' || $productUrls === []) {
            return [];
        }

        $productIndex = $this->buildProductIndex($productUrls);
        if ($productIndex === []) {
            return [];
        }

        // Base URL (pagination collapsed)
        $baseListingUrl = $this->normalizeListingPaginationUrl($listingUrl);
        $baseKey = $this->normalizeMatchKey($baseListingUrl);

        $found = [];
        $queue = [$baseListingUrl];
        $visited = [];
        $maxPages = 50;

        while ($queue !== [] && count($visited) < $maxPages) {
            $current = array_shift($queue);
            if (!is_string($current) || trim($current) === '') {
                continue;
            }

            $current = trim($current);
            $currentKey = $this->normalizeMatchKey($current);
            if ($currentKey === '' || isset($visited[$currentKey])) {
                continue;
            }
            $visited[$currentKey] = true;

            $html = $this->fetchHtml($current, $timeout, $userAgent);
            if ($html === '') {
                continue;
            }

            $crawler = new Crawler($html, $current);

            $candidates = ($listingKind === 'category')
                ? $this->extractLinksFromCategoryDom($crawler)
                : $this->extractLinksFromIndexDom($crawler);

            foreach ($candidates as $href) {
                $href = trim($href);
                if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                    continue;
                }

                $abs = $this->resolveUrl($current, $href);
                if ($abs === '') {
                    continue;
                }

                $key = $this->normalizeMatchKey($abs);
                if ($key === '') {
                    continue;
                }

                // Product match
                if (isset($productIndex[$key])) {
                    $found[$productIndex[$key]] = true;
                    continue;
                }

                // Pagination detection: base/page/<n>
                if ($this->isPaginationUrlForListing($key, $baseKey)) {
                    $queue[] = $this->normalizeListingPaginationUrl($abs);
                }
            }
        }

        $out = array_keys($found);
        sort($out, SORT_STRING);

        return $out;
    }

    /**
     * Category pages: only keep links inside <article>.
     *
     * @param Crawler $crawler
     * @return array<int, string>
     */
    private function extractLinksFromCategoryDom(Crawler $crawler): array
    {
        $urls = [];

        $articles = $crawler->filter('article');
        if ($articles->count() === 0) {
            return [];
        }

        foreach ($articles as $article) {
            if (!$article instanceof \DOMElement) {
                continue;
            }

            $sub = new Crawler($article, $crawler->getBaseHref() ?? null);
            foreach ($sub->filter('a[href]') as $a) {
                if (!$a instanceof \DOMElement) {
                    continue;
                }
                $href = trim((string) $a->getAttribute('href'));
                if ($href !== '') {
                    $urls[] = $href;
                }
            }
        }

        return $urls;
    }

    /**
     * Index pages (/animations/...):
     * - If articles exist, use them (same behavior)
     * - Else, use .col-lg-4 cards, and prefer h3.bt-title > a and a.bt-readmore
     *
     * @param Crawler $crawler
     * @return array<int, string>
     */
    private function extractLinksFromIndexDom(Crawler $crawler): array
    {
        // 1) Prefer articles if present
        if ($crawler->filter('article')->count() > 0) {
            return $this->extractLinksFromCategoryDom($crawler);
        }

        $urls = [];

        // 2) Fallback: cards (.col-lg-4)
        $cards = $crawler->filter('.col-lg-4');
        if ($cards->count() === 0) {
            // Last resort: still scan all links (but will be filtered by product index anyway)
            foreach ($crawler->filter('a[href]') as $a) {
                if ($a instanceof \DOMElement) {
                    $href = trim((string) $a->getAttribute('href'));
                    if ($href !== '') {
                        $urls[] = $href;
                    }
                }
            }

            return $urls;
        }

        foreach ($cards as $card) {
            if (!$card instanceof \DOMElement) {
                continue;
            }

            $sub = new Crawler($card, $crawler->getBaseHref() ?? null);

            // Priority: title link
            $title = $sub->filter('h3.bt-title a[href]');
            if ($title->count() > 0) {
                $href = trim((string) $title->first()->attr('href'));
                if ($href !== '') {
                    $urls[] = $href;
                }
            }

            // Priority: readmore
            $readMore = $sub->filter('a.bt-readmore[href]');
            if ($readMore->count() > 0) {
                $href = trim((string) $readMore->first()->attr('href'));
                if ($href !== '') {
                    $urls[] = $href;
                }
            }

            // Fallback inside card
            if ($title->count() === 0 && $readMore->count() === 0) {
                foreach ($sub->filter('a[href]') as $a) {
                    if ($a instanceof \DOMElement) {
                        $href = trim((string) $a->getAttribute('href'));
                        if ($href !== '') {
                            $urls[] = $href;
                        }
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Build index: match-key => canonical product URL.
     *
     * @param array<int, string> $productUrls
     * @return array<string, string>
     */
    private function buildProductIndex(array $productUrls): array
    {
        $index = [];

        foreach ($productUrls as $url) {
            if (!is_string($url) || trim($url) === '') {
                continue;
            }

            $canonical = rtrim(trim($url), '/');
            if ($canonical === '') {
                continue;
            }

            $key = $this->normalizeMatchKey($canonical);
            if ($key === '') {
                continue;
            }

            // Keep first canonical encountered
            $index[$key] ??= $canonical;
        }

        return $index;
    }

    /**
     * Fetch HTML.
     *
     * @param string $url
     * @param int    $timeout
     * @param string $userAgent
     *
     * @return string
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
     * Normalize paginated listing URLs into their base listing URL.
     * Supports patterns like:
     * - .../page/2
     * - .../page/2/
     *
     * @param string $url
     * @return string
     */
    private function normalizeListingPaginationUrl(string $url): string
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
        $path = preg_replace('~^(.*?)(?:/page/\d+)$~i', '$1', $path) ?? $path;

        return sprintf('%s://%s%s', $scheme, $host, $path);
    }

    /**
     * Check if a normalized match-key is a pagination URL for a base listing.
     *
     * @param string $matchKey
     * @param string $baseMatchKey
     * @return bool
     */
    private function isPaginationUrlForListing(string $matchKey, string $baseMatchKey): bool
    {
        if ($matchKey === '' || $baseMatchKey === '') {
            return false;
        }

        return preg_match('~^' . preg_quote($baseMatchKey, '~') . '/page/\d+$~i', $matchKey) === 1;
    }

    /**
     * Normalize to a stable match-key.
     * - scheme-insensitive
     * - remove query + fragment
     * - trailing slash-insensitive
     * - host lowercased
     *
     * Output: "host/path" (no scheme)
     * Example: "up-animations.fr/animation-cirque"
     *
     * @param string $url
     * @return string
     */
    private function normalizeMatchKey(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // remove fragment
        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $url = substr($url, 0, $hashPos);
        }

        // remove query
        $qPos = strpos($url, '?');
        if ($qPos !== false) {
            $url = substr($url, 0, $qPos);
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $host = strtolower((string) $parts['host']);
        $path = (string) ($parts['path'] ?? '');
        $path = '/' . ltrim($path, '/');

        // trailing slash-insensitive
        $path = rtrim($path, '/');

        return $host . $path;
    }

    /**
     * Resolve relative URLs against a base absolute URL.
     *
     * @param string $baseUrl
     * @param string $href
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

        // Protocol-relative
        if (str_starts_with($href, '//')) {
            return $scheme . ':' . $href;
        }

        // Root-relative
        if (str_starts_with($href, '/')) {
            return $scheme . '://' . $host . $port . $href;
        }

        // Relative to current path
        $basePath = (string) ($base['path'] ?? '/');
        if ($basePath === '' || $basePath[0] !== '/') {
            $basePath = '/' . ltrim($basePath, '/');
        }

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
