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
 * CrawlCategoryUrlsCommand.
 *
 * @doc php bin/console app:crawl:category-urls var/crawler/contents.json
 * Crawl listing pages listed in contents.json and attach matching product URLs.
 *
 * @doc php bin/console app:crawl:category-urls var/crawler/contents.json --timeout=20 --user-agent="MyCrawler/1.0"
 * Same behavior with custom HTTP settings.
 *
 * It scans each listing page for links pointing to any URL in the "products" bucket,
 * and stores the result in:
 * - categories[<url>]["urls"] (array of product URLs)
 * - indexes[<url>]["urls"] (array of product URLs)
 *
 * Existing data in contents.json is preserved (merge + unique).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:crawl:category-urls',
    description: 'Enrich categories + indexes in contents.json by detecting linked product URLs.',
)]
class CrawlCategoryUrlsCommand extends Command
{
    public function __construct(
        private readonly CategoryUrlsCrawlerService $listingCrawler,
        private readonly ProductContentsCrawlerService $jsonIo,
        private readonly string $projectDir,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to contents.json', 'var/crawler/contents.json')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout (seconds)', '15')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'SymfonyListingUrlCrawler/1.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $filePath = $this->absPath((string)$input->getOption('file'));
        $timeout = max(1, (int)$input->getOption('timeout'));
        $userAgent = (string)$input->getOption('user-agent');

        if (!$fs->exists($filePath)) {
            $io->error(sprintf('File not found: %s', $filePath));
            return Command::FAILURE;
        }

        $map = $this->jsonIo->readContentsJson($filePath);

        $products = (isset($map['products']) && is_array($map['products'])) ? array_keys($map['products']) : [];
        $categories = (isset($map['categories']) && is_array($map['categories'])) ? array_keys($map['categories']) : [];
        $indexes = (isset($map['indexes']) && is_array($map['indexes'])) ? array_keys($map['indexes']) : [];

        $io->title('Listing URLs crawler (categories + indexes)');
        $io->writeln(sprintf('File      : <info>%s</info>', $filePath));
        $io->writeln(sprintf('Products  : <info>%d</info>', count($products)));
        $io->writeln(sprintf('Categories: <info>%d</info>', count($categories)));
        $io->writeln(sprintf('Indexes   : <info>%d</info>', count($indexes)));

        if ($products === []) {
            $io->warning('No products found in contents.json. Nothing to match.');
            return Command::SUCCESS;
        }

        $total = count($categories) + count($indexes);
        if ($total === 0) {
            $io->warning('No categories/indexes found in contents.json.');
            return Command::SUCCESS;
        }

        $io->progressStart($total);

        // 1) Categories
        foreach ($categories as $listingUrl) {
            $payload = is_array($map['categories'][$listingUrl] ?? null) ? $map['categories'][$listingUrl] : [];

            $found = $this->listingCrawler->extractCategoryProductUrls((string)$listingUrl, $products, $timeout, $userAgent);

            $existing = $payload['urls'] ?? [];
            if (!is_array($existing)) {
                $existing = [];
            }

            $payload['urls'] = $this->uniqueSorted(array_merge($existing, $found));
            $map['categories'][$listingUrl] = $payload;

            $io->progressAdvance();
        }

        // 2) Indexes
        foreach ($indexes as $listingUrl) {
            $payload = is_array($map['indexes'][$listingUrl] ?? null) ? $map['indexes'][$listingUrl] : [];

            $found = $this->listingCrawler->extractIndexProductUrls((string)$listingUrl, $products, $timeout, $userAgent);

            $existing = $payload['urls'] ?? [];
            if (!is_array($existing)) {
                $existing = [];
            }

            $payload['urls'] = $this->uniqueSorted(array_merge($existing, $found));
            $map['indexes'][$listingUrl] = $payload;

            $io->progressAdvance();
        }

        $io->progressFinish();

        // Ensure target directory exists
        $fs->mkdir(\dirname($filePath));

        $this->jsonIo->writeContentsJson($filePath, $map);

        $io->success('contents.json updated with categories + indexes URLs.');

        return Command::SUCCESS;
    }

    /**
     * Convert relative paths to absolute paths from projectDir.
     *
     * @param string $path
     *
     * @return string
     */
    private function absPath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // Already absolute?
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('~^[A-Za-z]:\\\\~', $path) === 1) {
            return $path;
        }

        return $this->projectDir . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Return unique string list sorted.
     *
     * @param array<int, mixed> $values
     *
     * @return array<int, string>
     */
    private function uniqueSorted(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            if (is_string($v) && trim($v) !== '') {
                $out[] = trim($v);
            }
        }

        $out = array_values(array_unique($out));
        sort($out, SORT_STRING);

        return $out;
    }
}
