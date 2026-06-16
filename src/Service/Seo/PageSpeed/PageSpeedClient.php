<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * PageSpeedClient.
 *
 * Calls the Google PageSpeed Insights v5 API for one page across the configured
 * strategies (mobile, desktop) and returns a normalized, storable report. Each call
 * is a real Lighthouse run server-side at Google: only publicly reachable URLs work.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PageSpeedClient
{
    private const string ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    private const array CATEGORIES = ['PERFORMANCE', 'ACCESSIBILITY', 'BEST_PRACTICES', 'SEO'];

    private const array VALID_STRATEGIES = ['mobile', 'desktop'];

    private const int TIMEOUT = 70;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PageSpeedResultParser $parser,
        private readonly string $apiKey,
        private readonly bool $enabled,
        private readonly string $strategies,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled && '' !== trim($this->apiKey);
    }

    /**
     * @return array<int, string>
     */
    public function strategies(): array
    {
        $list = array_values(array_filter(array_map(
            static fn (string $s): string => strtolower(trim($s)),
            explode(',', $this->strategies)
        ), static fn (string $s): bool => in_array($s, self::VALID_STRATEGIES, true)));

        return [] === $list ? ['mobile'] : array_values(array_unique($list));
    }

    /**
     * Measure one page across every configured strategy.
     *
     * @return array<string, mixed>
     *
     * @throws PageSpeedException
     */
    public function measure(string $pageUrl, ?string $locale = 'fr'): array
    {
        if (!$this->isEnabled()) {
            throw PageSpeedException::disabled();
        }

        $ownHost = parse_url($pageUrl, PHP_URL_HOST);
        $ownHost = is_string($ownHost) ? $ownHost : null;

        $report = ['url' => $pageUrl, 'strategies' => []];
        foreach ($this->strategies() as $strategy) {
            $report['strategies'][$strategy] = $this->parser->parse(
                $this->runPagespeed($pageUrl, $strategy, $locale),
                $ownHost
            );
        }

        return $report;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PageSpeedException
     */
    private function runPagespeed(string $pageUrl, string $strategy, ?string $locale): array
    {
        $query = http_build_query(array_filter([
            'url' => $pageUrl,
            'key' => $this->apiKey,
            'strategy' => $strategy,
            'locale' => $locale,
        ], static fn ($v): bool => null !== $v && '' !== $v));

        $categories = implode('&', array_map(static fn (string $c): string => 'category='.$c, self::CATEGORIES));
        $endpoint = self::ENDPOINT.'?'.$query.'&'.$categories;

        try {
            $response = $this->httpClient->request('GET', $endpoint, ['timeout' => self::TIMEOUT]);
            $status = $response->getStatusCode();
            if ($status >= 400) {
                throw PageSpeedException::api($status, $this->errorDetail($response->getContent(false)));
            }

            return $response->toArray(false);
        } catch (TransportExceptionInterface $e) {
            $this->logger?->error('PageSpeed transport error: '.$e->getMessage(), ['url' => $pageUrl, 'strategy' => $strategy]);
            throw PageSpeedException::transport($e->getMessage());
        } catch (PageSpeedException $e) {
            throw $e;
        } catch (ExceptionInterface|\JsonException $e) {
            throw PageSpeedException::api(0, $e->getMessage());
        }
    }

    private function errorDetail(string $body): ?string
    {
        $data = json_decode($body, true);
        if (is_array($data) && isset($data['error']['message']) && is_string($data['error']['message'])) {
            return $data['error']['message'];
        }

        return null;
    }
}
