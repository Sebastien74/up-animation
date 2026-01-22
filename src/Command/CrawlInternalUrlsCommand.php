<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * CrawlInternalUrlsCommand.
 *
 * @doc php bin/console app:crawl:internal-urls https://up-animations.fr --max-urls=2000 --max-depth=12
 * Crawl the site starting from https://up-animations.fr, collecting internal URLs only.
 * Stops when 2000 unique URLs are found, and explores links up to 12 levels deep.
 *
 * @doc php bin/console app:crawl:internal-urls https://up-animations.fr --max-urls=2000 --max-depth=12 --ignore-query
 * Same crawl strategy, but strips query strings (e.g. ?page=2) from URLs.
 * Recommended to avoid URL explosion on sites generating infinite variants via parameters.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:crawl:internal-urls',
    description: 'Crawl a website and extract internal URLs only (BFS, dedup, limit, JSON output).',
)]
class CrawlInternalUrlsCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('startUrl', InputArgument::OPTIONAL, 'Start URL to crawl', 'https://up-animations.fr')
            ->addOption('max-urls', null, InputOption::VALUE_REQUIRED, 'Max URLs to collect', '2000')
            ->addOption('max-depth', null, InputOption::VALUE_REQUIRED, 'Max crawl depth (0 = unlimited)', '10')
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Max in-flight HTTP requests', '10')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output JSON file (recommended: var/crawler/urls.json)', 'var/crawler/urls.json')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'SymfonyCrawler/1.0')
            ->addOption('ignore-query', null, InputOption::VALUE_NONE, 'Drop query string (?a=b) to avoid URL explosion')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startUrl = (string) $input->getArgument('startUrl');
        $maxUrls = max(1, (int) $input->getOption('max-urls'));
        $maxDepth = max(0, (int) $input->getOption('max-depth'));
        $concurrency = max(1, (int) $input->getOption('concurrency'));
        $outputFile = trim((string) $input->getOption('output'));
        $userAgent = (string) $input->getOption('user-agent');
        $ignoreQuery = (bool) $input->getOption('ignore-query');

        $startUrl = $this->normalizeUrl($startUrl, $startUrl, $ignoreQuery);
        if ($startUrl === null) {
            $io->error('Invalid start URL.');
            return Command::FAILURE;
        }

        $startHost = parse_url($startUrl, PHP_URL_HOST);
        if (!is_string($startHost) || $startHost === '') {
            $io->error('Could not determine host from start URL.');
            return Command::FAILURE;
        }

        // Allow-list of internal hosts (we will add the "final host" after first request if redirected)
        $allowedHosts = [$startHost => true];
        // Also accept www/non-www variant right away
        $this->addWwwVariants($allowedHosts, $startHost);

        $io->title('Internal URL crawler');
        $io->writeln(sprintf('Start: <info>%s</info>', $startUrl));
        $io->writeln(sprintf('Max  : <info>%d</info> URLs | Depth: <info>%s</info> | Concurrency: <info>%d</info>',
            $maxUrls,
            $maxDepth === 0 ? 'unlimited' : (string) $maxDepth,
            $concurrency
        ));
        $io->writeln(sprintf('Output: <info>%s</info> (JSON)', $outputFile));

        // Queue stores [url, depth]
        $queue = new \SplQueue();
        $queue->enqueue([$startUrl, 0]);

        // visited URLs set
        $visited = [];
        $visited[$this->hashUrl($startUrl)] = true;

        // collected URLs (stable order)
        $collected = [$startUrl];

        $io->progressStart($maxUrls);

        $inFlight = [];

        while ((!$queue->isEmpty() || $inFlight !== []) && count($collected) < $maxUrls) {

            while (
                !$queue->isEmpty()
                && count($inFlight) < $concurrency
                && count($collected) < $maxUrls
            ) {

                [$url, $depth] = $queue->dequeue();

                // Depth guard: 0 means unlimited
                if ($maxDepth !== 0 && $depth > $maxDepth) {
                    continue;
                }

                $inFlight[] = [
                    'url' => $url,
                    'depth' => $depth,
                    'response' => $this->httpClient->request('GET', $url, [
                        'headers' => [
                            'User-Agent' => $userAgent,
                            'Accept' => 'text/html,application/xhtml+xml',
                        ],
                        'max_redirects' => 5,
                        'timeout' => 15,
                    ]),
                ];
            }

            $batch = array_splice($inFlight, 0, 1);
            if ($batch === []) {
                continue;
            }

            $job = $batch[0];
            $requestedUrl = $job['url'];
            $depth = $job['depth'];
            $response = $job['response'];

            try {

                $status = $response->getStatusCode();
                if ($status < 200 || $status >= 400) {
                    continue;
                }

                // If redirected, use the effective URL as base, and allow its host too
                $effectiveUrl = $response->getInfo('url');
                if (is_string($effectiveUrl) && $effectiveUrl !== '') {
                    $effectiveUrl = $this->normalizeUrl($effectiveUrl, $effectiveUrl, $ignoreQuery);
                    if ($effectiveUrl) {
                        $effectiveHost = parse_url($effectiveUrl, PHP_URL_HOST);
                        if (is_string($effectiveHost) && $effectiveHost !== '') {
                            $allowedHosts[$effectiveHost] = true;
                            $this->addWwwVariants($allowedHosts, $effectiveHost);
                        }
                        // Prefer effective URL as base for relative resolution
                        $requestedUrl = $effectiveUrl;
                    }
                }

                $headers = $response->getHeaders(false);
                $contentType = $headers['content-type'][0] ?? '';
                if (!is_string($contentType) || stripos($contentType, 'text/html') === false) {
                    continue;
                }

                $html = $response->getContent(false);
                if (!is_string($html) || $html === '') {
                    continue;
                }

                $newUrls = $this->extractInternalLinks($html, $requestedUrl, $allowedHosts, $ignoreQuery);

                foreach ($newUrls as $foundUrl) {

                    if (count($collected) >= $maxUrls) {
                        break;
                    }

                    $hash = $this->hashUrl($foundUrl);
                    if (isset($visited[$hash])) {
                        continue;
                    }

                    $visited[$hash] = true;
                    $collected[] = $foundUrl;
                    $queue->enqueue([$foundUrl, $depth + 1]);

                    $io->progressAdvance();
                }
            } catch (TransportExceptionInterface|\Throwable) {
                continue;
            }
        }

        $io->progressFinish();

        $payload = [
            'start' => $startUrl,
            'max_urls' => $maxUrls,
            'max_depth' => $maxDepth,
            'ignore_query' => $ignoreQuery,
            'allowed_hosts' => array_keys($allowedHosts),
            'count' => count($collected),
            'urls' => $collected,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        @mkdir(\dirname($outputFile), 0777, true);
        file_put_contents($outputFile, $json . PHP_EOL);

        $io->success(sprintf('Collected %d internal URLs.', count($collected)));
        $io->writeln(sprintf('JSON written to: <info>%s</info>', $outputFile));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, bool> $allowedHosts
     */
    private function addWwwVariants(array &$allowedHosts, string $host): void
    {
        if (str_starts_with($host, 'www.')) {
            $allowedHosts[substr($host, 4)] = true;
        } else {
            $allowedHosts['www.' . $host] = true;
        }
    }

    /**
     * @param array<string, bool> $allowedHosts
     * @return array<int, string>
     */
    private function extractInternalLinks(string $html, string $baseUrl, array $allowedHosts, bool $ignoreQuery): array
    {
        $crawler = new Crawler($html, $baseUrl);

        $links = [];
        foreach ($crawler->filter('a[href]') as $node) {
            $href = $node->getAttribute('href');
            if (!is_string($href) || $href === '') {
                continue;
            }

            $href = trim($href);
            if ($href === '' ||
                str_starts_with($href, 'mailto:') ||
                str_starts_with($href, 'tel:') ||
                str_starts_with($href, 'javascript:')
            ) {
                continue;
            }

            // Resolve relative URL -> absolute
            $absolute = UriResolver::resolve($href, $baseUrl);

            $normalized = $this->normalizeUrl($absolute, $baseUrl, $ignoreQuery);
            if ($normalized === null) {
                continue;
            }

            $host = parse_url($normalized, PHP_URL_HOST);
            if (!is_string($host) || $host === '' || !isset($allowedHosts[$host])) {
                continue;
            }

            $links[] = $normalized;
        }

        return array_values(array_unique($links));
    }

    private function normalizeUrl(string $maybeUrl, string $baseUrl, bool $ignoreQuery): ?string
    {
        $maybeUrl = trim($maybeUrl);

        // Drop fragment
        $hashPos = strpos($maybeUrl, '#');
        if ($hashPos !== false) {
            $maybeUrl = trim(substr($maybeUrl, 0, $hashPos));
        }

        if ($maybeUrl === '') {
            return null;
        }

        // Basic validation
        $scheme = parse_url($maybeUrl, PHP_URL_SCHEME);
        if (is_string($scheme)) {
            $schemeLower = strtolower($scheme);
            if (!in_array($schemeLower, ['http', 'https'], true)) {
                return null;
            }
        }

        // parse_url requires absolute; if not absolute, bail (we resolve before calling normalize in practice)
        $parts = parse_url($maybeUrl);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        // Normalize trailing slash (except root)
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        $queryStr = '';
        if (!$ignoreQuery) {
            $query = $parts['query'] ?? '';
            if (is_string($query) && $query !== '') {
                $queryStr = '?' . $query;
            }
        }

        return sprintf(
            '%s://%s%s%s%s',
            strtolower((string) $parts['scheme']),
            (string) $parts['host'],
            $port,
            $path,
            $queryStr
        );
    }

    private function hashUrl(string $url): string
    {
        return sha1($url);
    }
}
