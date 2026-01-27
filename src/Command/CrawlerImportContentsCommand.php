<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\Import\ContentsImporterInterface;
use App\Service\Development\Import\ProductContentsCrawlerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * ImportContentsCommand.
 *
 * Read contents.json and dispatch each bucket to the matching importer service.
 *
 * Supported buckets:
 * - products
 * - categories
 * - indexes
 * - pages
 *
 * @doc php bin/console app:import:contents --file=var/crawler/contents.json
 * @doc php bin/console app:import:contents --file=var/crawler/contents.json --dry-run
 * @doc php bin/console app:import:contents --file=var/crawler/contents.json --only=products
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:import:contents',
    description: 'Import buckets from contents.json into the database (products/categories/indexes/pages).',
)]
class CrawlerImportContentsCommand extends Command
{
    /**
     * @param iterable<ContentsImporterInterface> $importers
     */
    public function __construct(
        #[AutowireIterator('app.contents_importer')]
        private readonly iterable $importers,
        private readonly ProductContentsCrawlerService $jsonIo,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to contents.json', 'var/crawler/contents.json')
            ->addOption('meta-file', 'm', InputOption::VALUE_REQUIRED, 'Path to contents.json', 'var/crawler/metas.json')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not write to database (read-only simulation)')
            ->addOption('only', null, InputOption::VALUE_REQUIRED, 'Import only one bucket (products|categories|indexes|pages)', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $filePath = $this->absPath((string) $input->getOption('file'));
        $dryRun = (bool) $input->getOption('dry-run');
        $only = trim((string) $input->getOption('only'));
        $metasPath = $this->absPath((string) $input->getOption('meta-file'));

        if (!$fs->exists($filePath)) {
            $io->error(sprintf('File not found: %s', $filePath));
            return Command::FAILURE;
        }

        if (!$fs->exists($metasPath)) {
            $io->error(sprintf('File not found: %s', $metasPath));
            return Command::FAILURE;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false || trim($raw) === '') {
            $io->error('contents.json is empty or unreadable.');
            return Command::FAILURE;
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $io->error('Invalid JSON: ' . $e->getMessage());
            return Command::FAILURE;
        }

        if (!is_array($data)) {
            $io->error('Invalid JSON structure (root must be an object).');
            return Command::FAILURE;
        }

        $metas = $this->jsonIo->readContentsJson($metasPath);
        $buckets = ['products', 'categories', 'indexes', 'pages'];

        if ($only !== '') {
            if (!in_array($only, $buckets, true)) {
                $io->error(sprintf('Unknown bucket "%s". Allowed: %s', $only, implode(', ', $buckets)));
                return Command::FAILURE;
            }
            $buckets = [$only];
        }

        $payloadByBucket = [];
        $total = 0;

        foreach ($buckets as $bucket) {
            $payload = (isset($data[$bucket]) && is_array($data[$bucket])) ? $data[$bucket] : [];
            $payloadByBucket[$bucket] = $payload;

            // Each bucket payload is a map: url => { contents: ..., ... }
            $total += count($payload);
        }

        $io->title('Contents import');
        $io->writeln(sprintf('File   : <info>%s</info>', $filePath));
        $io->writeln(sprintf('Buckets: <info>%s</info>', implode(', ', $buckets)));
        $io->writeln(sprintf('Items  : <info>%d</info>', $total));
        if ($dryRun) {
            $io->note('Dry-run enabled: no DB changes will be persisted.');
        }

        if ($total === 0) {
            $io->warning('Nothing to import (all selected buckets are empty).');
            return Command::SUCCESS;
        }

        $progress = new ProgressBar($output, $total);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $progress->setMessage('Starting...');
        $progress->start();

        foreach ($buckets as $bucket) {
            $bucketPayload = $payloadByBucket[$bucket];

            if ($bucketPayload === []) {
                continue;
            }

            $importer = $this->findImporter($bucket);

            if (!$importer) {
                // No importer: we still need to advance progress to avoid freezing display.
                foreach (array_keys($bucketPayload) as $unused) {
                    $progress->setMessage(sprintf('Skipping %s (no importer)', $bucket));
                    $progress->advance();
                }
                continue;
            }

            $progress->setMessage(sprintf('Importing %s', $bucket));
            $importer->import($bucket, $bucketPayload, $metas, $io, $progress, $dryRun);
        }

        $progress->setMessage('Done');
        $progress->finish();
        $io->newLine(2);

        $io->success('Import completed.');

        return Command::SUCCESS;
    }

    private function findImporter(string $bucket): ?ContentsImporterInterface
    {
        foreach ($this->importers as $importer) {
            if ($importer instanceof ContentsImporterInterface && $importer->supports($bucket)) {
                return $importer;
            }
        }

        return null;
    }

    /**
     * Convert relative paths to absolute paths from projectDir.
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
