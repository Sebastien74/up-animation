<?php

declare(strict_types=1);

namespace App\Service\Development\Import;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * ProductContentsCrawlerService.
 *
 * Scrape product pages and extract structured "picto" blocks (WPBakery) as a normalized contents payload.
 * Reads/writes the contents.json file used by the crawl commands.
 *
 * Output format per product URL:
 *  contents:
 *    <urlized picto label>: "<comma-separated strong texts from the target paragraph>"
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProductContentsCrawlerService
{
    private AsciiSlugger $slugger;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
        $this->slugger = new AsciiSlugger('fr');
    }

    /**
     * Read contents.json and return the decoded array.
     *
     * @param string $path
     *
     * @return array<string, mixed>
     */
    public function readContentsJson(string $path): array
    {
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Dump the given array as JSON to disk.
     *
     * @param string               $path
     * @param array<string, mixed> $data
     *
     * @return void
     */
    public function writeContentsJson(string $path, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        file_put_contents($path, $json . PHP_EOL);
    }

    /**
     * Enrich the "products" section of contents.json:
     * - For each product URL, fill "contents" with a map:
     *   slug (urlized picto strong) => "value list" extracted from the outer wrapper paragraph.
     *
     * @param array<string, mixed> $contentsMap
     * @param int                  $timeout
     * @param string               $userAgent
     *
     * @return array<string, mixed>
     */
    public function enrichProducts(array $contentsMap, int $timeout, string $userAgent): array
    {
        if (!isset($contentsMap['products']) || !is_array($contentsMap['products'])) {
            return $contentsMap;
        }

        foreach ($contentsMap['products'] as $url => $payload) {
            if (!is_string($url) || trim($url) === '') {
                continue;
            }

            if (!is_array($payload)) {
                $payload = [];
            }

            $payload['contents'] = $this->extractProductContents(trim($url), $timeout, $userAgent);
            $contentsMap['products'][$url] = $payload;
        }

        return $contentsMap;
    }

    /**
     * Extract contents from a product page according to your WPBakery structure:
     * - Find each <p class="picto">.
     * - For each picto:
     *   - key: urlized <strong> inside p.picto
     *   - value: comma-separated list of <strong> texts found in the first direct child paragraph "> p"
     *            of the nearest OUTER "div.wpb_wrapper" where that "> p" is NOT ".picto".
     *
     * @param string $url
     * @param int    $timeout
     * @param string $userAgent
     *
     * @return array<string, string>
     */
    public function extractProductContents(string $url, int $timeout, string $userAgent): array
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

            $crawler = new Crawler($html, $url);

            $out = [];

            foreach ($crawler->filter('p.picto') as $pictoEl) {
                $pictoCrawler = new Crawler($pictoEl);

                // Key = urlized strong text inside the picto paragraph
                $strongNode = $pictoCrawler->filter('strong');
                if ($strongNode->count() === 0) {
                    continue;
                }

                $label = $this->cleanText($strongNode->first()->text(''));
                if ($label === '') {
                    continue;
                }

                $key = $this->urlize($label);
                if ($key === '') {
                    continue;
                }

                // Find the nearest OUTER wpb_wrapper where the direct child paragraph is NOT the picto paragraph
                $wrapperEl = $this->closestOuterWpbWrapperWithNonPictoDirectP($pictoEl);
                if (!$wrapperEl) {
                    $out[$key] = '';
                    continue;
                }

                $wrapper = new Crawler($wrapperEl);

                // Direct paragraphs under the outer wrapper (exclude p.picto)
                $directPs = $wrapper->filter(':scope > p')->reduce(function (Crawler $node): bool {
                    $class = (string) $node->attr('class');
                    return !preg_match('~(^|\s)picto(\s|$)~', $class);
                });

                if ($directPs->count() === 0) {
                    $out[$key] = '';
                    continue;
                }

                // Choose the first usable paragraph:
                // - Prefer joining <strong> texts inside it
                // - Fallback to paragraph text if no <strong>
                $value = '';
                foreach ($directPs as $pEl) {
                    $pCrawler = new Crawler($pEl);

                    $candidate = $this->extractStrongListFromParagraph($pCrawler);
                    if ($candidate === '') {
                        $candidate = $this->cleanText($pEl->textContent ?? '');
                    }

                    if ($candidate !== '') {
                        $value = $candidate;
                        break;
                    }
                }

                $out[$key] = $value;
            }

            ksort($out);

            return $out;
        } catch (TransportExceptionInterface) {
            return [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Find the nearest ancestor "div.wpb_wrapper" that contains at least one direct child "<p>"
     * that is NOT ".picto".
     *
     * This avoids selecting the inner wrapper (where "> p" is the picto itself) and targets
     * the outer wrapper (where "> p" contains the actual values like "Evenementiel, Team Building").
     *
     * @param \DOMNode $node
     *
     * @return \DOMElement|null
     */
    private function closestOuterWpbWrapperWithNonPictoDirectP(\DOMNode $node): ?\DOMElement
    {
        $current = $node instanceof \DOMElement ? $node : $node->parentNode;

        while ($current instanceof \DOMElement) {
            if (strtolower($current->tagName) === 'div' && $this->hasClass($current, 'wpb_wrapper')) {
                // Must contain at least one direct <p> that is NOT .picto
                foreach ($current->childNodes as $child) {
                    if (!$child instanceof \DOMElement) {
                        continue;
                    }

                    if (strtolower($child->tagName) !== 'p') {
                        continue;
                    }

                    $classAttr = ' ' . ($child->getAttribute('class') ?? '') . ' ';
                    if (str_contains($classAttr, ' picto ')) {
                        continue;
                    }

                    return $current;
                }
            }

            $current = $current->parentNode;
        }

        return null;
    }

    /**
     * Extract a comma-separated list from the <strong> elements inside a paragraph.
     *
     * @param Crawler $pCrawler
     *
     * @return string
     */
    private function extractStrongListFromParagraph(Crawler $pCrawler): string
    {
        $items = [];

        foreach ($pCrawler->filter('strong') as $strongEl) {
            $txt = $this->cleanText($strongEl->textContent ?? '');
            if ($txt !== '') {
                $items[] = str_replace(['Evenementiel'], ['Évènementiel'], $txt);
            }
        }

        return $items !== [] ? implode(', ', $items) : '';
    }

    /**
     * Check if a DOMElement has a given CSS class.
     *
     * @param \DOMElement $el
     * @param string      $class
     *
     * @return bool
     */
    private function hasClass(\DOMElement $el, string $class): bool
    {
        $classes = ' ' . ($el->getAttribute('class') ?? '') . ' ';
        return str_contains($classes, ' ' . $class . ' ');
    }

    /**
     * Urlize a label (Urlizer-like behavior).
     *
     * @param string $label
     *
     * @return string
     */
    private function urlize(string $label): string
    {
        return strtolower((string) $this->slugger->slug($label));
    }

    /**
     * Normalize extracted text (trim + collapse whitespace).
     *
     * @param string $text
     *
     * @return string
     */
    private function cleanText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('~\s+~u', ' ', $text) ?? $text;

        return trim($text);
    }
}
