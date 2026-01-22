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

#[AsCommand(
    name: 'app:crawl:product-contents',
    description: 'Enrich products contents in contents.yaml by scraping WPBakery picto blocks.',
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
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to contents.yaml', 'var/crawler/contents.yaml')
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

        $map = $this->crawler->readContentsYaml($filePath);

        $productsCount = (isset($map['products']) && is_array($map['products'])) ? count($map['products']) : 0;

        $io->title('Product contents crawler');
        $io->writeln(sprintf('File    : <info>%s</info>', $filePath));
        $io->writeln(sprintf('Products: <info>%d</info>', $productsCount));

        if ($productsCount === 0) {
            $io->warning('No products found in contents.yaml.');
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

        $this->crawler->writeContentsYaml($filePath, $map);

        $io->success('contents.yaml updated with product contents.');

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