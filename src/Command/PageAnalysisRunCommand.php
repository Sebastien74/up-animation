<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Service\Admin\PageAnalysisRecorder;
use App\Service\Admin\PageAnalyzerInterface;
use App\Service\Seo\PageSpeed\PublicPageUrlResolver;
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
 * processed later. Covers the Page, Newscast and Product interfaces. Public URLs are
 * built by PublicPageUrlResolver (same logic as the admin tool and PageSpeed): the
 * home page resolves to the domain root, newscasts/products to their real module path.
 *
 * @doc php bin/console app:analysis-page:run --max-urls=500 --max-seconds=120
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:analysis-page:run',
    description: 'Analyze published front pages over HTTP and historize their performance score.',
)]
final class PageAnalysisRunCommand extends Command
{
    /**
     * Analyzable interfaces: name => entity FQCN.
     */
    private const array INTERFACES = [
        'page' => 'App\Entity\Layout\Page',
        'newscast' => 'App\Entity\Module\Newscast\Newscast',
        'catalogproduct' => 'App\Entity\Module\Catalog\Product',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly PageAnalyzerInterface $analyzer,
        private readonly PageAnalysisRecorder $recorder,
        private readonly PublicPageUrlResolver $urlResolver,
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
            $io->section(sprintf('Website #%d', $website->getId()));

            $ok = 0;
            $failed = 0;
            $crawled = 0;
            $stopped = false;
            $deadline = time() + $maxSeconds;

            foreach (self::INTERFACES as $interface => $class) {
                if (!class_exists($class) || $stopped) {
                    continue;
                }

                foreach ($this->onlineEntities($website, $class) as $entity) {
                    foreach ($this->onlineUrls($entity) as $url) {
                        if ($crawled >= $maxUrls) {
                            $stopped = true;
                            break 2;
                        }

                        $publicUrl = $this->urlResolver->resolve($website, $url, $interface, $entity, $class);
                        $report = null === $publicUrl ? null : $this->analyzeUrl($publicUrl, (string) $url->getCode(), $timeout, $userAgent);

                        if (null === $report) {
                            ++$failed;
                        } else {
                            $this->recorder->record($website, $url->getCode(), $url->getLocale(), $report, 'cron');
                            ++$ok;
                        }
                        ++$crawled;

                        if (time() >= $deadline) {
                            $stopped = true;
                            break 2;
                        }
                    }
                }
            }

            $io->writeln(sprintf(
                '%d analyzed, %d failed%s.',
                $ok,
                $failed,
                $stopped ? ', stopped on budget' : '',
            ));

            $totalOk += $ok;
            $totalFailed += $failed;
        }

        $io->success(sprintf('Page analysis done: %d analyzed, %d failed.', $totalOk, $totalFailed));

        return Command::SUCCESS;
    }

    /**
     * Entities of an interface owning at least one online URL on the website.
     *
     * @return array<int, object>
     */
    private function onlineEntities(Website $website, string $class): array
    {
        try {
            return $this->entityManager->createQuery(
                sprintf('SELECT DISTINCT e FROM %s e JOIN e.urls u WHERE e.website = :website AND u.online = true ORDER BY e.id DESC', $class)
            )
                ->setParameter('website', $website)
                ->getResult();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Online URLs owned by an entity.
     *
     * @return iterable<Url>
     */
    private function onlineUrls(object $entity): iterable
    {
        if (!method_exists($entity, 'getUrls')) {
            return [];
        }

        foreach ($entity->getUrls() as $url) {
            if ($url instanceof Url && $url->isOnline()) {
                yield $url;
            }
        }
    }

    /**
     * Fetch a page over HTTP and return its analysis report (null on failure).
     *
     * @return array<string, mixed>|null
     */
    private function analyzeUrl(string $fullUrl, string $code, int $timeout, string $userAgent): ?array
    {
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

        $ownHost = parse_url($fullUrl, PHP_URL_HOST);
        $report = $this->analyzer->analyze($html, $code, is_string($ownHost) ? $ownHost : null);
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
}
