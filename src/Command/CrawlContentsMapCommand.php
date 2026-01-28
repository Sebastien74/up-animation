<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\Import\UrlContentsClassifierService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;


/**
 * CrawlContentsMapCommand.
 *
 * @doc php bin/console app:crawl:contents-map --input=var/crawler/urls.json --output=var/crawler/contents.json --limit=2000
 * Build a structured contents.json map (products / indexes / categories / pages) from a flat urls.json list.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:crawl:contents-map',
    description: 'Read urls.json and generate a contents.json map grouped by URL patterns.',
)]
class CrawlContentsMapCommand extends Command
{
    public function __construct(
        private readonly UrlContentsClassifierService $classifier,
        private readonly string $projectDir,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('input', null, InputOption::VALUE_REQUIRED, 'Input JSON file containing URLs', 'var/crawler/urls.json')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output contents JSON file', 'var/crawler/contents.json')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max URLs to process', '2000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $inputPath = $this->absPath((string)$input->getOption('input'));
        $outputPath = $this->absPath((string)$input->getOption('output'));
        $limit = max(1, (int)$input->getOption('limit'));

        if (!$fs->exists($inputPath)) {
            $io->error(sprintf('Input file not found: %s', $inputPath));
            return Command::FAILURE;
        }

        $urls = $this->classifier->readUrlsFromJson($inputPath, $limit);
        if ($urls === []) {
            $io->warning('No URLs found in input JSON.');
            return Command::SUCCESS;
        }

        $io->title('Contents map builder');
        $io->writeln(sprintf('Input : <info>%s</info>', $inputPath));
        $io->writeln(sprintf('Output: <info>%s</info>', $outputPath));
        $io->writeln(sprintf('URLs  : <info>%d</info>', count($urls)));

        // Preserve already scraped "contents" blocks if contents.json exists
        $existingMap = $fs->exists($outputPath) ? $this->classifier->readContentsMapFromJson($outputPath) : [];
        $map = $this->classifier->classify($urls, $existingMap);

        $fs->mkdir(\dirname($outputPath));
        $json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        file_put_contents($outputPath, $json . PHP_EOL);

        $io->success(sprintf(
            'Generated contents map: products=%d, indexes=%d, categories=%d, pages=%d',
            count($map['products']),
            count($map['indexes']),
            count($map['categories']),
            count($map['pages']),
        ));

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
