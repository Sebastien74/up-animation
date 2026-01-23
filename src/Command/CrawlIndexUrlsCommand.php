<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\Import\CategoryUrlsCrawlerService;
use App\Service\Development\Import\ProductContentsCrawlerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CrawlIndexUrlsCommand.
 *
 * @doc php bin/console app:crawl:index-urls var/crawler/contents.json
 * Crawl index pages listed in contents.json and attach matching product URLs.
 *
 * It scans each index page for links (ONLY inside <article>) pointing to any URL
 * in the "products" bucket, follows pagination (/page/N), and stores the result in:
 * indexes[<indexUrl>]["urls"] (array of URLs).
 *
 * Existing data in contents.json is preserved (merge + unique).
 *
 * @author Sébastien FOURNIER
 */
#[AsCommand(
    name: 'app:crawl:index-urls',
    description: 'Enrich indexes in contents.json by detecting linked product URLs.',
)]
class CrawlIndexUrlsCommand extends Command
{
    public function __construct(
        private readonly CategoryUrlsCrawlerService $listingCrawler,
        private readonly ProductContentsCrawlerService $jsonIo,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to contents.json', 'var/crawler/contents.json')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout (seconds)', '15')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'SymfonyIndexUrlCrawler/1.0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $filePath = $this->absPath((string) $input->getOption('file'));
        $timeout = max(1, (int) $input->getOption('timeout'));
        $userAgent = (string) $input->getOption('user-agent');

        if (!$fs->exists($filePath)) {
            $io->error(sprintf('File not found: %s', $filePath));
            return Command::FAILURE;
        }

        $map = $this->jsonIo->readContentsJson($filePath);

        $products = (isset($map['products']) && is_array($map['products'])) ? array_keys($map['products']) : [];
        $indexes = (isset($map['indexes']) && is_array($map['indexes'])) ? array_keys($map['indexes']) : [];

        $io->title('Index URLs crawler');
        $io->writeln(sprintf('File    : <info>%s</info>', $filePath));
        $io->writeln(sprintf('Products: <info>%d</info>', count($products)));
        $io->writeln(sprintf('Indexes : <info>%d</info>', count($indexes)));

        if ($products === []) {
            $io->warning('No products found in contents.json. Nothing to match.');
            return Command::SUCCESS;
        }

        if ($indexes === []) {
            $io->warning('No indexes found in contents.json.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($indexes));

        foreach ($indexes as $indexUrl) {
            $payload = is_array($map['indexes'][$indexUrl] ?? null) ? $map['indexes'][$indexUrl] : [];

            $found = $this->listingCrawler->extractIndexProductUrls((string) $indexUrl, $products, $timeout, $userAgent);

            $existing = $payload['urls'] ?? [];
            if (!is_array($existing)) {
                $existing = [];
            }

            $payload['urls'] = $this->uniqueSorted(array_merge($existing, $found));

            $map['indexes'][$indexUrl] = $payload;

            $io->progressAdvance();
        }

        $io->progressFinish();

        $fs->mkdir(\dirname($filePath));
        $this->jsonIo->writeContentsJson($filePath, $map);

        $io->success('contents.json updated with indexes URLs.');

        return Command::SUCCESS;
    }

    private function absPath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('~^[A-Za-z]:\\\\~', $path) === 1) {
            return $path;
        }

        return $this->projectDir . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @param array<int, mixed> $values
     *
     * @return array<int, string>
     */
    private function uniqueSorted(array $values): array
    {
        $values = array_values(array_unique(array_filter($values, static fn ($v) => is_string($v) && trim($v) !== '')));
        sort($values);

        return $values;
    }
}
