<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Repository\Seo\UrlRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * DeprecationCrawlService.
 *
 * Runtime crawl: visits every front URL (online entities) and the satisfiable
 * admin GET routes through in-process sub-requests, so the deprecations they
 * trigger get logged. Reads the deprecation journal delta after each URL to
 * attribute findings to the page that produced them. Internal, on-demand only:
 * never wired into the normal request cycle and disabled in production.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class DeprecationCrawlService
{
    private const string LINE_PATTERN = '/^\[[^\]]+\] (?<channel>[\w.\-]+)\.[A-Z]+: (?<message>.*)$/';
    private const string PACKAGE_PATTERN = '/Since (?<package>[\w\/.\-]+) [\d.]+:/';
    private const array ADMIN_ROUTE_VARS = ['website', '_locale'];

    private readonly string $journalPath;
    private readonly string $storePath;

    public function __construct(
        private readonly HttpKernelInterface $httpKernel,
        private readonly UrlRepository $urlRepository,
        private readonly RouterInterface $router,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly RequestStack $requestStack,
        KernelInterface $kernel,
    ) {
        $this->journalPath = rtrim($kernel->getLogDir(), '/\\').\DIRECTORY_SEPARATOR.$kernel->getEnvironment().'.deprecations.log';
        $this->storePath = $kernel->getProjectDir().'/var/cache/deprecation-crawl.json';
    }

    public function urlCount(): int
    {
        return \count($this->buildUrls());
    }

    /**
     * Visit a single URL (by index) and return the deprecations it triggered.
     *
     * @return array{
     *     total: int,
     *     processed: int,
     *     done: bool,
     *     url: ?string,
     *     type: ?string,
     *     findings: list<array{area: string, package: string, message: string, location: string}>
     * }
     */
    public function crawlOne(int $index): array
    {
        if ($index <= 0) {
            $this->resetStore($this->buildUrls());
        }

        $store = $this->readStore() ?? ['urls' => [], 'findings' => []];
        $urls = $store['urls'] ?? [];
        $total = \count($urls);

        if ($index < 0 || $index >= $total) {
            return ['total' => $total, 'processed' => $total, 'done' => true, 'url' => null, 'type' => null, 'findings' => []];
        }

        $entry = $urls[$index];
        $before = is_file($this->journalPath) ? (int) filesize($this->journalPath) : 0;
        $this->visit($entry['url']);
        clearstatcache(true, $this->journalPath);
        $findings = $this->readJournalDelta($before, $entry);
        $this->appendStore($findings);

        $processed = $index + 1;

        return [
            'total' => $total,
            'processed' => $processed,
            'done' => $processed >= $total,
            'url' => $entry['url'],
            'type' => $entry['type'],
            'findings' => $findings,
        ];
    }

    /**
     * Last persisted crawl results, for a trace across page visits.
     *
     * @return array{
     *     available: bool,
     *     crawledAt: ?string,
     *     total: int,
     *     unique: int,
     *     byArea: list<array{name: string, count: int}>,
     *     byPackage: list<array{name: string, count: int}>,
     *     findings: list<array{area: string, package: string, message: string, location: string}>
     * }
     */
    public function lastResults(): array
    {
        $store = $this->readStore();
        $findings = $store['findings'] ?? [];

        $byArea = [];
        $byPackage = [];
        foreach ($findings as $finding) {
            $byArea[$finding['area']] = ($byArea[$finding['area']] ?? 0) + 1;
            $byPackage[$finding['package']] = ($byPackage[$finding['package']] ?? 0) + 1;
        }
        arsort($byArea);
        arsort($byPackage);

        return [
            'available' => null !== $store,
            'crawledAt' => $store['crawledAt'] ?? null,
            'total' => \count($findings),
            'unique' => \count(array_unique(array_column($findings, 'message'))),
            'byArea' => $this->toPairs($byArea),
            'byPackage' => $this->toPairs($byPackage),
            'findings' => $findings,
        ];
    }

    public function clearCrawl(): bool
    {
        return !is_file($this->storePath) || @unlink($this->storePath);
    }

    /**
     * @return list<array{url: string, type: string}>
     */
    private function buildUrls(): array
    {
        $websiteId = $this->coreLocator->website()?->id;
        if (null === $websiteId) {
            return [];
        }

        $urls = [];
        foreach ($this->urlRepository->findOnlineForCrawl($websiteId) as $row) {
            $code = trim((string) $row['code']);
            $urls[] = ['url' => '/'.ltrim($code, '/'), 'type' => 'front'];
        }

        foreach ($this->adminUrls($websiteId) as $url) {
            $urls[] = ['url' => $url, 'type' => 'admin'];
        }

        return $urls;
    }

    /**
     * @return list<string>
     */
    private function adminUrls(int $websiteId): array
    {
        $urls = [];
        foreach ($this->router->getRouteCollection() as $name => $route) {
            if (!str_starts_with($name, 'admin_')) {
                continue;
            }
            $methods = $route->getMethods();
            if ([] !== $methods && !\in_array('GET', $methods, true)) {
                continue;
            }

            $variables = $route->compile()->getVariables();
            $defaults = $route->getDefaults();
            $requiredExtra = array_filter(
                array_diff($variables, self::ADMIN_ROUTE_VARS),
                static fn (string $var): bool => !\array_key_exists($var, $defaults),
            );
            if ([] !== $requiredExtra) {
                continue;
            }

            $params = \in_array('website', $variables, true) ? ['website' => $websiteId] : [];
            try {
                $urls[] = $this->router->generate($name, $params, UrlGeneratorInterface::ABSOLUTE_PATH);
            } catch (\Throwable) {
                continue;
            }
        }

        return $urls;
    }

    private function visit(string $url): void
    {
        $main = $this->requestStack->getMainRequest();
        $server = ['HTTP_X_INTERNAL_CRAWLER' => '1'];
        if (null !== $main) {
            $server['HTTP_HOST'] = $main->getHost();
            if ($main->isSecure()) {
                $server['HTTPS'] = 'on';
            }
        }

        try {
            $request = Request::create($this->toPath($url), 'GET', [], [], [], $server);
            $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST, true);
        } catch (\Throwable) {
            // A failing page must never break the crawl.
        }
    }

    private function toPath(string $url): string
    {
        $path = parse_url($url, \PHP_URL_PATH) ?: '/';
        $query = parse_url($url, \PHP_URL_QUERY);

        return $query ? $path.'?'.$query : $path;
    }

    /**
     * @param array{url: string, type: string} $entry
     *
     * @return list<array{area: string, package: string, message: string, location: string}>
     */
    private function readJournalDelta(int $offset, array $entry): array
    {
        if (!is_file($this->journalPath)) {
            return [];
        }

        $handle = fopen($this->journalPath, 'rb');
        if (false === $handle) {
            return [];
        }

        $findings = [];
        try {
            fseek($handle, $offset);
            while (false !== ($line = fgets($handle))) {
                $line = rtrim($line);
                if ('' === $line || 1 !== preg_match(self::LINE_PATTERN, $line, $matches) || 'deprecation' !== $matches['channel']) {
                    continue;
                }
                $message = $this->stripContext($matches['message']);
                $findings[] = [
                    'area' => $entry['type'],
                    'package' => $this->packageFromMessage($message),
                    'message' => $message,
                    'location' => $entry['url'],
                ];
            }
        } finally {
            fclose($handle);
        }

        return $findings;
    }

    private function stripContext(string $message): string
    {
        $clean = preg_replace('/\s+(\{.*\}|\[\])\s+(\{.*\}|\[\])\s*$/', '', $message);

        return trim($clean ?? $message);
    }

    private function packageFromMessage(string $message): string
    {
        return 1 === preg_match(self::PACKAGE_PATTERN, $message, $matches) ? $matches['package'] : 'autre';
    }

    /**
     * @param array<string, int> $map
     *
     * @return list<array{name: string, count: int}>
     */
    private function toPairs(array $map): array
    {
        $pairs = [];
        foreach ($map as $name => $count) {
            $pairs[] = ['name' => $name, 'count' => $count];
        }

        return $pairs;
    }

    /**
     * @param list<array{url: string, type: string}> $urls
     */
    private function resetStore(array $urls): void
    {
        @mkdir(\dirname($this->storePath), 0777, true);
        file_put_contents($this->storePath, (string) json_encode([
            'crawledAt' => (new \DateTime())->format(\DateTimeInterface::ATOM),
            'urls' => $urls,
            'findings' => [],
        ]));
    }

    /**
     * @param list<array{area: string, package: string, message: string, location: string}> $findings
     */
    private function appendStore(array $findings): void
    {
        $store = $this->readStore() ?? ['urls' => [], 'findings' => []];
        $store['findings'] = array_merge($store['findings'] ?? [], $findings);
        file_put_contents($this->storePath, (string) json_encode($store));
    }

    /**
     * @return array{crawledAt?: string, urls?: list<array{url: string, type: string}>, findings?: list<array<string, mixed>>}|null
     */
    private function readStore(): ?array
    {
        if (!is_file($this->storePath)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($this->storePath), true);

        return \is_array($data) ? $data : null;
    }
}
