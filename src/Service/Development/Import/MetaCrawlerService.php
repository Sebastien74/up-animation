<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class MetaCrawlerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Crawl a list of URLs and extract SEO/social metas + JSON-LD.
     *
     * @param array<int, string> $urls
     * @param int                $timeout
     * @param string             $userAgent
     *
     * @return array<string, array<string, mixed>> Key is the URL, value is the meta payload.
     */
    public function crawlUrls(array $urls, int $timeout, string $userAgent): array
    {
        $metasByUrl = [];

        foreach ($urls as $url) {
            if (!is_string($url) || trim($url) === '') {
                continue;
            }

            $url = trim($url);
            $metasByUrl[$url] = $this->fetchAndExtract($url, $timeout, $userAgent);
        }

        return $metasByUrl;
    }

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

        $urls = array_values(array_unique(array_filter($urls, static fn ($u) => is_string($u) && $u !== '')));

        if (count($urls) > $limit) {
            $urls = array_slice($urls, 0, $limit);
        }

        return $urls;
    }

    /**
     * Fetch a page and extract metas. Always returns a complete structure.
     *
     * @param string $url
     * @param int    $timeout
     * @param string $userAgent
     *
     * @return array<string, mixed>
     */
    public function fetchAndExtract(string $url, int $timeout, string $userAgent): array
    {
        // Default structure (keep keys even if empty)
        $result = [
            'meta-title' => '',
            'meta-description' => '',
            'meta-robots' => '', // Empty string if not present (as requested)
            'og' => [],          // property => content
            'article' => [],     // property => content
            'twitter' => [],     // name => content
            'ld+json' => [],     // list of raw JSON strings
            'error' => '',       // network/parse error if any
        ];

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
                $result['error'] = sprintf('HTTP %d', $status);
                return $result;
            }

            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? '';
            if (!is_string($contentType) || stripos($contentType, 'text/html') === false) {
                // Not HTML => keep empty metas
                return $result;
            }

            $html = $response->getContent(false);
            if (!is_string($html) || $html === '') {
                return $result;
            }

            $crawler = new Crawler($html, $url);

            // Title
            $titleNode = $crawler->filter('head > title');
            if ($titleNode->count() > 0) {
                $result['meta-title'] = trim((string) $titleNode->text(''));
            }

            // Meta description / robots
            $result['meta-description'] = $this->getMetaByName($crawler, 'description') ?? '';
            $result['meta-robots'] = $this->getMetaByName($crawler, 'robots') ?? '';

            // og:* / article:* / twitter:*
            $result['og'] = $this->getMetaByPropertyPrefix($crawler, 'og:');
            $result['article'] = $this->getMetaByPropertyPrefix($crawler, 'article:');
            $result['twitter'] = $this->getMetaByNamePrefix($crawler, 'twitter:');

            // LD+JSON scripts (raw)
            $result['ld+json'] = $this->getLdJsonScripts($crawler);

            return $result;
        } catch (TransportExceptionInterface $e) {
            $result['error'] = 'TransportException: ' . $e->getMessage();
            return $result;
        } catch (\Throwable $e) {
            $result['error'] = 'Exception: ' . $e->getMessage();
            return $result;
        }
    }

    /**
     * Get a meta content by its "name" attribute.
     *
     * @param Crawler $crawler
     * @param string  $name
     *
     * @return string|null
     */
    private function getMetaByName(Crawler $crawler, string $name): ?string
    {
        $node = $crawler->filter(sprintf('meta[name="%s"]', addslashes($name)));
        if ($node->count() === 0) {
            return null;
        }

        $content = (string) $node->first()->attr('content');
        return trim($content);
    }

    /**
     * Get metas where property starts with a prefix (e.g. og:, article:).
     * Returns map "property" => "content".
     *
     * @param Crawler $crawler
     * @param string  $prefix
     *
     * @return array<string, string>
     */
    private function getMetaByPropertyPrefix(Crawler $crawler, string $prefix): array
    {
        $out = [];

        foreach ($crawler->filter('meta[property]') as $node) {
            $prop = (string) $node->getAttribute('property');
            if ($prop === '' || !str_starts_with($prop, $prefix)) {
                continue;
            }

            $content = trim((string) $node->getAttribute('content'));
            $out[$prop] = $content; // keep empty content if present in DOM
        }

        ksort($out);

        return $out;
    }

    /**
     * Get metas where name starts with a prefix (e.g. twitter:).
     * Returns map "name" => "content".
     *
     * @param Crawler $crawler
     * @param string  $prefix
     *
     * @return array<string, string>
     */
    private function getMetaByNamePrefix(Crawler $crawler, string $prefix): array
    {
        $out = [];

        foreach ($crawler->filter('meta[name]') as $node) {
            $name = (string) $node->getAttribute('name');
            if ($name === '' || !str_starts_with($name, $prefix)) {
                continue;
            }

            $content = trim((string) $node->getAttribute('content'));
            $out[$name] = $content;
        }

        ksort($out);

        return $out;
    }

    /**
     * Extract raw JSON-LD scripts from the page.
     *
     * @param Crawler $crawler
     *
     * @return array<int, string>
     */
    private function getLdJsonScripts(Crawler $crawler): array
    {
        $ld = [];

        foreach ($crawler->filter('script[type="application/ld+json"]') as $node) {
            $json = trim($node->textContent ?? '');
            if ($json !== '') {
                $ld[] = $json;
            }
        }

        return $ld;
    }
}