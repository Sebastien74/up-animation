<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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

    private const int WARMUP_TIMEOUT = 15;

    private const int SAMPLES = 3;

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

        // Wake the FPM pool before sampling so the first run is not penalized by a cold start.
        $this->warmUp($pageUrl);

        // Fire every sample of every strategy concurrently to keep wall-time near a single run.
        $pending = [];
        foreach ($this->strategies() as $strategy) {
            $endpoint = $this->buildEndpoint($pageUrl, $strategy, $locale);
            for ($i = 0; $i < self::SAMPLES; ++$i) {
                $pending[$strategy][] = $this->httpClient->request('GET', $endpoint, ['timeout' => self::TIMEOUT]);
            }
        }

        $report = ['url' => $pageUrl, 'strategies' => []];
        foreach ($pending as $strategy => $responses) {
            $report['strategies'][$strategy] = $this->medianOf($responses, $ownHost)
                ?? $this->parser->parse($this->runPagespeed($pageUrl, $strategy, $locale), $ownHost);
        }

        return $report;
    }

    /**
     * Best-effort page hit to wake the FPM pool (and trigger on-demand assets) before measuring.
     */
    private function warmUp(string $pageUrl): void
    {
        try {
            $this->httpClient->request('GET', $pageUrl, ['timeout' => self::WARMUP_TIMEOUT])->getContent(false);
        } catch (\Throwable $e) {
            $this->logger?->warning('PageSpeed warm-up failed: '.$e->getMessage(), ['url' => $pageUrl]);
        }
    }

    /**
     * Parse each sample and return the run with the median performance score, or null if all failed.
     *
     * @param array<int, ResponseInterface> $responses
     *
     * @return array<string, mixed>|null
     */
    private function medianOf(array $responses, ?string $ownHost): ?array
    {
        $parsed = [];
        foreach ($responses as $response) {
            try {
                if ($response->getStatusCode() >= 400) {
                    continue;
                }
                $parsed[] = $this->parser->parse($response->toArray(false), $ownHost);
            } catch (\Throwable $e) {
                $this->logger?->warning('PageSpeed sample failed: '.$e->getMessage());
            }
        }

        if ([] === $parsed) {
            return null;
        }

        usort($parsed, static fn (array $a, array $b): int => ($a['scores']['performance'] ?? 0) <=> ($b['scores']['performance'] ?? 0));

        return $parsed[intdiv(count($parsed), 2)];
    }

    private function buildEndpoint(string $pageUrl, string $strategy, ?string $locale): string
    {
        $query = http_build_query(array_filter([
            'url' => $pageUrl,
            'key' => $this->apiKey,
            'strategy' => $strategy,
            'locale' => $locale,
        ], static fn ($v): bool => null !== $v && '' !== $v));

        $categories = implode('&', array_map(static fn (string $c): string => 'category='.$c, self::CATEGORIES));

        return self::ENDPOINT.'?'.$query.'&'.$categories;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PageSpeedException
     */
    private function runPagespeed(string $pageUrl, string $strategy, ?string $locale): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->buildEndpoint($pageUrl, $strategy, $locale), ['timeout' => self::TIMEOUT]);
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
