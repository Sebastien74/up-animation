<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\Import\MetaCrawlerService;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:crawl:metas',
    description: 'Extract metas from URLs in urls.yaml and write metas.yaml.',
)]
class CrawlMetasCommand extends Command
{
    public function __construct(
        private readonly MetaCrawlerService $metaCrawler,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('input', null, InputOption::VALUE_REQUIRED, 'Input YAML file containing URLs', 'var/crawler/urls.yaml')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output YAML metas file', 'var/crawler/metas.yaml')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max URLs to process', '2000')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout (seconds)', '15')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'SymfonyMetaCrawler/1.0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $inputPath = $this->absPath((string) $input->getOption('input'));
        $outputPath = $this->absPath((string) $input->getOption('output'));

        $limit = max(1, (int) $input->getOption('limit'));
        $timeout = max(1, (int) $input->getOption('timeout'));
        $userAgent = (string) $input->getOption('user-agent');

        if (!$fs->exists($inputPath)) {
            $io->error(sprintf('Input file not found: %s', $inputPath));
            return Command::FAILURE;
        }

        $urls = $this->metaCrawler->readUrlsFromYaml($inputPath, $limit);

        if ($urls === []) {
            $io->warning('No URLs found in input YAML.');
            return Command::SUCCESS;
        }

        $io->title('Meta crawler');
        $io->writeln(sprintf('Input : <info>%s</info>', $inputPath));
        $io->writeln(sprintf('Output: <info>%s</info>', $outputPath));
        $io->writeln(sprintf('URLs  : <info>%d</info>', count($urls)));

        $io->progressStart(count($urls));

        $metasByUrl = [];
        foreach ($urls as $url) {
            $metasByUrl[$url] = $this->metaCrawler->fetchAndExtract($url, $timeout, $userAgent);
            $io->progressAdvance();
        }

        $io->progressFinish();

        $fs->mkdir(\dirname($outputPath));
        file_put_contents($outputPath, Yaml::dump($metasByUrl, 6, 2, Yaml::DUMP_OBJECT_AS_MAP));

        $io->success(sprintf('Metas extracted for %d URLs.', count($metasByUrl)));

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

        return $this->coreLocator->projectDir() . DIRECTORY_SEPARATOR . $path;
    }
}
