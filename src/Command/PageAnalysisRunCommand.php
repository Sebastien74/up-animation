<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Service\Admin\PageAnalysisRecorder;
use App\Service\Admin\PageAnalyzerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * PageAnalysisRunCommand.
 *
 * Periodically analyzes published front pages (perf & rendering) over HTTP and
 * historizes the indicative score in upa_seo_page_analysis, so results can be
 * processed later. Covers every interface owning an online seo_url (Page,
 * Newscast, Product). It fetches the live public pages: no preview, no admin
 * context required, and no impact on front navigation.
 *
 * @doc php bin/console app:page-analysis:run --max-urls=500 --max-seconds=120
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:page-analysis:run',
    description: 'Analyze published front pages over HTTP and historize their performance score.',
)]
final class PageAnalysisRunCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly PageAnalyzerInterface $analyzer,
        private readonly PageAnalysisRecorder $recorder,
        private readonly string $appProtocol,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('website', null, InputOption::VALUE_REQUIRED, 'Restrict to a single website id')
            ->addOption('max-urls', null, InputOption::VALUE_REQUIRED, 'Max URLs to analyze per website', '500')
            ->addOption('max-seconds', null, InputOption::VALUE_REQUIRED, 'Time budget per website (graceful stop, shared hosting safe)', '120')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout per request (seconds)', '30')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'PageAnalysis/1.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxUrls = max(1, (int) $input->getOption('max-urls'));
        $maxSeconds = max(1, (int) $input->getOption('max-seconds'));
        $timeout = max(1, (int) $input->getOption('timeout'));
        $userAgent = (string) $input->getOption('user-agent');

        $totalOk = 0;
        $totalFailed = 0;

        foreach ($this->resolveWebsites($input) as $website) {
            $baseUrls = $this->baseUrls($website);
            $defaultBase = $baseUrls['_default'] ?? null;
            if (null === $defaultBase) {
                $io->warning(sprintf('Website #%d has no domain, skipped.', $website->getId()));
                continue;
            }

            $io->section($defaultBase);

            $urls = $this->entityManager->getRepository(Url::class)->findOnlineForCrawl((int) $website->getId());
            $urls = array_slice($urls, 0, $maxUrls);
            if ([] === $urls) {
                $io->writeln('No online URL.');
                continue;
            }

            $ok = 0;
            $failed = 0;
            $stopped = false;
            $deadline = time() + $maxSeconds;
            $io->progressStart(count($urls));

            foreach ($urls as $row) {
                $code = $row['code'] ?? null;
                $locale = $row['locale'] ?? null;
                // Fetch each page on the domain matching its locale (fallback: default domain).
                $base = $baseUrls[(string) $locale] ?? $defaultBase;
                $report = $this->analyzeUrl($base, (string) $code, $timeout, $userAgent);

                if (null === $report) {
                    ++$failed;
                } else {
                    $this->recorder->record($website, $code, $locale, $report);
                    ++$ok;
                }

                $io->progressAdvance();

                if (time() >= $deadline) {
                    $stopped = true;
                    break;
                }
            }

            $io->progressFinish();
            $io->writeln(sprintf(
                '%d analyzed, %d failed%s (%d total).',
                $ok,
                $failed,
                $stopped ? ', stopped on time budget' : '',
                count($urls),
            ));

            $totalOk += $ok;
            $totalFailed += $failed;
        }

        $io->success(sprintf('Page analysis done: %d analyzed, %d failed.', $totalOk, $totalFailed));

        return Command::SUCCESS;
    }

    /**
     * Fetch a page over HTTP and return its analysis report (null on failure).
     *
     * @return array<string, mixed>|null
     */
    private function analyzeUrl(string $baseUrl, string $code, int $timeout, string $userAgent): ?array
    {
        $path = trim($code, '/');
        $fullUrl = rtrim($baseUrl, '/').('' === $path ? '/' : '/'.$path);

        try {
            $start = microtime(true);
            $response = $this->httpClient->request('GET', $fullUrl, [
                'headers' => ['User-Agent' => $userAgent, 'Accept' => 'text/html,application/xhtml+xml'],
                'timeout' => $timeout,
                'max_redirects' => 5,
            ]);
            if ($response->getStatusCode() >= 400) {
                return null;
            }
            $html = $response->getContent(false);
            $renderMs = (int) round((microtime(true) - $start) * 1000);
        } catch (TransportExceptionInterface|\Throwable) {
            return null;
        }

        $report = $this->analyzer->analyze($html, $code);
        $report['meta']['renderMs'] = $renderMs;

        return $report;
    }

    /**
     * @return iterable<Website>
     */
    private function resolveWebsites(InputInterface $input): iterable
    {
        $repository = $this->entityManager->getRepository(Website::class);

        if ($websiteId = $input->getOption('website')) {
            $website = $repository->find((int) $websiteId);

            return $website ? [$website] : [];
        }

        return $repository->findAll();
    }

    /**
     * Base URLs keyed by locale (for multi-domain-per-locale sites), plus a '_default'
     * fallback (the default domain, or the first one found).
     *
     * @return array<string, string|null>
     */
    private function baseUrls(Website $website): array
    {
        $domains = $this->entityManager->getRepository(Domain::class)
            ->findBy(['configuration' => $website->getConfiguration()]);

        $map = ['_default' => null];
        foreach ($domains as $domain) {
            $name = $domain->getName();
            if (!$name) {
                continue;
            }
            $url = $this->appProtocol.'://'.$name;
            if ($domain->getLocale()) {
                $map[$domain->getLocale()] = $url;
            }
            if ($domain->isAsDefault() || null === $map['_default']) {
                $map['_default'] = $url;
            }
        }

        return $map;
    }
}
