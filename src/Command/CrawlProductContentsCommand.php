<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\Import\ProductContentsCrawlerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;


/**
 * CrawlProductContentsCommand.
 *
 * @doc php bin/console app:crawl:product-contents --file=var/crawler/contents.json
 * For each product URL in contents.json, scrape WPBakery "picto" blocks and populate the "contents" payload.
 *
 * @doc php bin/console app:crawl:product-contents --file=var/crawler/contents.json --timeout=20 --user-agent="MyBot/1.0"
 * Same crawl, with custom timeout and User-Agent.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:crawl:product-contents',
    description: 'Enrich products contents in contents.json by scraping WPBakery picto blocks.',
)]
class CrawlProductContentsCommand extends Command
{
    public function __construct(
        private readonly ProductContentsCrawlerService $crawler,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to contents.json', 'var/crawler/contents.json')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout (seconds)', '15')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'SymfonyProductContentCrawler/1.0')
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

        $map = $this->crawler->readContentsJson($filePath);

        $productsCount = (isset($map['products']) && is_array($map['products'])) ? count($map['products']) : 0;

        $io->title('Product contents crawler');
        $io->writeln(sprintf('File    : <info>%s</info>', $filePath));
        $io->writeln(sprintf('Products: <info>%d</info>', $productsCount));

        if ($productsCount === 0) {
            $io->warning('No products found in contents.json.');
            return Command::SUCCESS;
        }

        $io->progressStart($productsCount);

        // Enrich each product URL (progress-friendly)
        foreach (array_keys($map['products']) as $url) {
            $payload = is_array($map['products'][$url] ?? null) ? $map['products'][$url] : [];
            $payload['contents'] = $this->crawler->extractProductContents((string) $url, $timeout, $userAgent);
            $map['products'][$url] = $payload;

            $io->progressAdvance();
        }

        $io->progressFinish();

        // Ensure target directory exists
        $fs->mkdir(\dirname($filePath));

        $map = $this->crawler->readContentsJson($filePath);
        $map = $this->crawler->enrichProducts($map, $timeout, $userAgent);
        $this->crawler->writeContentsJson($filePath, $map);

        $io->success('contents.json updated with product contents.');

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
}