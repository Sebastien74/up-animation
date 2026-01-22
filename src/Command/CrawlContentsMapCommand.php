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
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:crawl:contents-map',
    description: 'Read urls.yaml and generate a contents.yaml map grouped by URL patterns.',
)]
class CrawlContentsMapCommand extends Command
{
    public function __construct(
        private readonly UrlContentsClassifierService $classifier,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('input', null, InputOption::VALUE_REQUIRED, 'Input YAML file containing URLs', 'var/crawler/urls.yaml')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output contents YAML file', 'var/crawler/contents.yaml')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max URLs to process', '2000')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $inputPath = $this->absPath((string) $input->getOption('input'));
        $outputPath = $this->absPath((string) $input->getOption('output'));
        $limit = max(1, (int) $input->getOption('limit'));

        if (!$fs->exists($inputPath)) {
            $io->error(sprintf('Input file not found: %s', $inputPath));
            return Command::FAILURE;
        }

        $urls = $this->classifier->readUrlsFromYaml($inputPath, $limit);
        if ($urls === []) {
            $io->warning('No URLs found in input YAML.');
            return Command::SUCCESS;
        }

        $io->title('Contents map builder');
        $io->writeln(sprintf('Input : <info>%s</info>', $inputPath));
        $io->writeln(sprintf('Output: <info>%s</info>', $outputPath));
        $io->writeln(sprintf('URLs  : <info>%d</info>', count($urls)));

        $map = $this->classifier->classify($urls);

        $fs->mkdir(\dirname($outputPath));
        file_put_contents($outputPath, Yaml::dump($map, 10, 2, Yaml::DUMP_OBJECT_AS_MAP));

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
