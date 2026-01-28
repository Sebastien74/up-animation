<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\Import\PagesUrlsCrawlerService;
use App\Service\Development\Import\ProductContentsCrawlerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * CrawlPagesUrlsCommand.
 *
 * @doc php bin/console app:crawl:pages-urls var/crawler/contents.json
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
    name: 'app:crawl:pages-urls',
    description: 'Enrich indexes in contents.json by detecting linked product URLs.',
)]
class CrawlPagesUrlsCommand extends Command
{
    public function __construct(
        private readonly PagesUrlsCrawlerService $crawler,
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
            ->addOption('meta-file', 'm', InputOption::VALUE_REQUIRED, 'Path to contents.json', 'var/crawler/metas.json')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout (seconds)', '15')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'SymfonyIndexUrlCrawler/1.0');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $filePath = $this->absPath((string)$input->getOption('file'));
        $metasPath = $this->absPath((string)$input->getOption('meta-file'));

        if (!$fs->exists($filePath)) {
            $io->error(sprintf('File not found: %s', $filePath));
            return Command::FAILURE;
        }

        if (!$fs->exists($metasPath)) {
            $io->error(sprintf('File not found: %s', $metasPath));
            return Command::FAILURE;
        }

        $map = $this->jsonIo->readContentsJson($filePath);
        $indexes = (isset($map['indexes']) && is_array($map['indexes'])) ? $map['indexes'] : [];
        $categories = (isset($map['categories']) && is_array($map['categories'])) ? $map['categories'] : [];
        $pages = (isset($map['pages']) && is_array($map['pages'])) ? $map['pages'] : [];

        $allPages = array_merge($indexes, $categories, $pages);
        $pagesIndex = array_merge($indexes, $categories);
        $metas = $this->jsonIo->readContentsJson($metasPath);

        $io->title('Pages URLs crawler');
        $io->writeln(sprintf('Indexes : <info>%d</info>', count($allPages)));

        if ($allPages === []) {
            $io->warning('No pages found in contents.json.');
            return Command::SUCCESS;
        }

        $io->progressStart(count($allPages));

        foreach ($pagesIndex as $indexUrl => $contents) {
            $metas = !empty($metas[$indexUrl]) ? $metas[$indexUrl] : [];
            $this->crawler->createPageIndex((string)$indexUrl, $contents, $metas);
            $io->progressAdvance();
        }

        foreach ($pages as $url => $contents) {
            $metas = !empty($metas[$url]) ? $metas[$url] : [];
            $this->crawler->createPage((string)$url, $contents, $metas);
            $io->progressAdvance();
        }

        $io->progressFinish();

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
}
