<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
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
 * CacheWarmupCommand.
 *
 * Rebuilds the front HTTP-facing caches (Doctrine result-cache, Twig {% cache %}
 * fragments) before they expire, so the first visit after an idle period stays
 * fast. URLs are pulled on the fly from each website's sitemap.xml: no entity
 * hydration here, just lightweight HTTP requests through the real web stack.
 *
 * @doc php bin/console app:cache:warmup --max-urls=300 --max-seconds=50
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:cache:warmup',
    description: 'Warm front page caches by requesting sitemap URLs over HTTP.',
)]
final class CacheWarmupCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly string $appProtocol,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('website', null, InputOption::VALUE_REQUIRED, 'Restrict to a single website id')
            ->addOption('max-urls', null, InputOption::VALUE_REQUIRED, 'Max URLs to warm per website', '300')
            ->addOption('max-seconds', null, InputOption::VALUE_REQUIRED, 'Time budget per website (graceful stop, shared hosting safe)', '50')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout per request (seconds)', '30')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'CacheWarmup/1.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxUrls = max(1, (int) $input->getOption('max-urls'));
        $maxSeconds = max(1, (int) $input->getOption('max-seconds'));
        $timeout = max(1, (int) $input->getOption('timeout'));
        $userAgent = (string) $input->getOption('user-agent');

        foreach ($this->resolveWebsites($input) as $website) {
            $baseUrl = $this->baseUrl($website);
            if (null === $baseUrl) {
                $io->warning(sprintf('Website #%d has no default domain, skipped.', $website->getId()));
                continue;
            }

            $io->section($baseUrl);

            $urls = $this->fetchSitemapUrls($baseUrl, $timeout, $userAgent, $maxUrls);
            if ([] === $urls) {
                $io->warning('No sitemap URL found.');
                continue;
            }

            [$ok, $failed, $stopped] = $this->warm($urls, $timeout, $userAgent, $maxSeconds, $io);

            $io->writeln(sprintf(
                '%d warmed, %d failed%s (%d total).',
                $ok,
                $failed,
                $stopped ? ', stopped on time budget' : '',
                count($urls),
            ));
        }

        $io->success('Cache warmup done.');

        return Command::SUCCESS;
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

    private function baseUrl(Website $website): ?string
    {
        $domain = $this->entityManager->getRepository(Domain::class)
            ->findOneBy(['configuration' => $website->getConfiguration(), 'asDefault' => true]);
        $name = $domain?->getName();

        return $name ? $this->appProtocol.'://'.$name : null;
    }

    /**
     * @return array<int, string>
     */
    private function fetchSitemapUrls(string $baseUrl, int $timeout, string $userAgent, int $maxUrls): array
    {
        try {
            $xml = $this->httpClient->request('GET', $baseUrl.'/sitemap.xml', [
                'headers' => ['User-Agent' => $userAgent],
                'timeout' => $timeout,
                'max_redirects' => 5,
            ])->getContent(false);
        } catch (TransportExceptionInterface|\Throwable) {
            return [];
        }

        if ('' === $xml || preg_match_all('~<loc>\s*(.+?)\s*</loc>~i', $xml, $matches) === 0) {
            return [];
        }

        $urls = array_values(array_unique(array_map(
            static fn (string $loc): string => html_entity_decode($loc, ENT_QUOTES | ENT_XML1),
            $matches[1],
        )));

        return array_slice($urls, 0, $maxUrls);
    }

    /**
     * Concurrent GET (HttpClient multiplexing); body is cancelled once the response
     * starts, the server has rendered the page by then and the caches are populated.
     *
     * @param array<int, string> $urls
     *
     * @return array{0: int, 1: int, 2: bool}
     */
    private function warm(array $urls, int $timeout, string $userAgent, int $maxSeconds, SymfonyStyle $io): array
    {
        $responses = [];
        foreach ($urls as $url) {
            $responses[] = $this->httpClient->request('GET', $url, [
                'headers' => ['User-Agent' => $userAgent],
                'timeout' => $timeout,
                'max_redirects' => 5,
            ]);
        }

        $ok = 0;
        $failed = 0;
        $stopped = false;
        $deadline = time() + $maxSeconds;

        $io->progressStart(count($urls));

        foreach ($this->httpClient->stream($responses) as $response => $chunk) {
            try {
                if ($chunk->isFirst()) {
                    $response->getStatusCode() < 400 ? $ok++ : $failed++;
                    $response->cancel();
                    $io->progressAdvance();
                }
            } catch (\Throwable) {
                ++$failed;
                $io->progressAdvance();
            }

            if (time() >= $deadline) {
                $stopped = true;
                break;
            }
        }

        foreach ($responses as $response) {
            $response->cancel();
        }

        $io->progressFinish();

        return [$ok, $failed, $stopped];
    }
}
