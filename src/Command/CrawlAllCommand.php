<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Application;

/**
 * CrawlAllCommand.
 *
 * @doc php bin/console app:crawl:all https://up-animations.fr --max-urls=2000 --max-depth=12 --ignore-query
 * Runs the full crawl pipeline:
 * internal-urls -> contents-map -> metas -> product-contents -> category-urls -> index-urls
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:crawl:all',
    description: 'Run the full crawl pipeline (internal-urls -> contents-map -> metas -> product-contents -> category-urls -> index-urls).',
)]
class CrawlAllCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('startUrl', InputArgument::OPTIONAL, 'Start URL to crawl', 'https://up-animations.fr')

            // internal-urls options
            ->addOption('max-urls', null, InputOption::VALUE_REQUIRED, 'Max URLs to collect', '2000')
            ->addOption('max-depth', null, InputOption::VALUE_REQUIRED, 'Max crawl depth (0 = unlimited)', '10')
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Max in-flight HTTP requests', '10')
            ->addOption('ignore-query', null, InputOption::VALUE_NONE, 'Drop query string (?a=b) to avoid URL explosion')

            // common HTTP options (metas/product/category)
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'HTTP timeout (seconds)', '15')
            ->addOption('user-agent', null, InputOption::VALUE_REQUIRED, 'User-Agent header', 'SymfonyCrawler/1.0')

            // file paths
            ->addOption('urls', null, InputOption::VALUE_REQUIRED, 'URLs JSON file', 'var/crawler/urls.json')
            ->addOption('contents', null, InputOption::VALUE_REQUIRED, 'Contents JSON file', 'var/crawler/contents.json')
            ->addOption('metas', null, InputOption::VALUE_REQUIRED, 'Metas JSON file', 'var/crawler/metas.json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Application|null $app */
        $app = $this->getApplication();
        if (!$app) {
            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);

        $startUrl     = (string) $input->getArgument('startUrl');
        $maxUrls      = (string) $input->getOption('max-urls');
        $maxDepth     = (string) $input->getOption('max-depth');
        $concurrency  = (string) $input->getOption('concurrency');
        $ignoreQuery  = (bool) $input->getOption('ignore-query');

        $timeout      = (string) $input->getOption('timeout');
        $userAgent    = (string) $input->getOption('user-agent');

        $urlsPath     = (string) $input->getOption('urls');
        $contentsPath = (string) $input->getOption('contents');
        $metasPath    = (string) $input->getOption('metas');

        $steps = [
            [
                'label' => 'internal-urls',
                'name'  => 'app:crawl:internal-urls',
                'input' => function () use ($startUrl, $maxUrls, $maxDepth, $concurrency, $ignoreQuery, $userAgent, $urlsPath): ArrayInput {
                    $args = [
                        'startUrl' => $startUrl,
                        '--max-urls' => $maxUrls,
                        '--max-depth' => $maxDepth,
                        '--concurrency' => $concurrency,
                        '--output' => $urlsPath,
                        '--user-agent' => $userAgent,
                    ];
                    if ($ignoreQuery) {
                        $args['--ignore-query'] = true;
                    }
                    return new ArrayInput($args);
                },
            ],
            [
                'label' => 'contents-map',
                'name'  => 'app:crawl:contents-map',
                'input' => function () use ($urlsPath, $contentsPath, $maxUrls): ArrayInput {
                    return new ArrayInput([
                        '--input' => $urlsPath,
                        '--output' => $contentsPath,
                        '--limit' => $maxUrls,
                    ]);
                },
            ],
            [
                'label' => 'metas',
                'name'  => 'app:crawl:metas',
                'input' => function () use ($urlsPath, $metasPath, $maxUrls, $timeout, $userAgent): ArrayInput {
                    // IMPORTANT: CrawlMetasCommand does NOT support --contents
                    return new ArrayInput([
                        '--input' => $urlsPath,
                        '--output' => $metasPath,
                        '--limit' => $maxUrls,
                        '--timeout' => $timeout,
                        '--user-agent' => $userAgent,
                    ]);
                },
            ],
            [
                'label' => 'product-contents',
                'name'  => 'app:crawl:product-contents',
                'input' => function () use ($contentsPath, $timeout, $userAgent): ArrayInput {
                    // IMPORTANT: CrawlProductContentsCommand uses --file, not --contents
                    return new ArrayInput([
                        '--file' => $contentsPath,
                        '--timeout' => $timeout,
                        '--user-agent' => $userAgent,
                    ]);
                },
            ],
            [
                'label' => 'category-urls',
                'name'  => 'app:crawl:category-urls',
                'input' => function () use ($contentsPath, $timeout, $userAgent): ArrayInput {
                    // IMPORTANT: CrawlCategoryUrlsCommand uses --file, not --contents
                    return new ArrayInput([
                        '--file' => $contentsPath,
                        '--timeout' => $timeout,
                        '--user-agent' => $userAgent,
                    ]);
                },
            ],
            [
                'label' => 'import-contents',
                'name'  => 'app:import:contents',
                'input' => function () use ($contentsPath, $timeout, $userAgent): ArrayInput {
                    // IMPORTANT: CrawlCategoryUrlsCommand uses --file, not --contents
                    return new ArrayInput([
                        '--file' => $contentsPath,
                        '--timeout' => $timeout,
                        '--user-agent' => $userAgent,
                    ]);
                },
            ],
        ];

        $io->title('Full crawl pipeline');
        $io->writeln(sprintf('Start URL : <info>%s</info>', $startUrl));
        $io->writeln(sprintf('urls.json : <info>%s</info>', $urlsPath));
        $io->writeln(sprintf('contents  : <info>%s</info>', $contentsPath));
        $io->writeln(sprintf('metas.json: <info>%s</info>', $metasPath));
        $io->newLine();

        $global = new ProgressBar($output, count($steps));
        $global->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $global->setMessage('Starting...');
        $global->start();

        foreach ($steps as $i => $step) {

            $global->setMessage(sprintf('Running %s', $step['label']));
            $global->display();

            /** @var ConsoleSectionOutput $section */
            $section = $output->section();
            $section->writeln('');
            $section->writeln(sprintf('<comment>==> Step %d/%d: %s (%s)</comment>', $i + 1, count($steps), $step['label'], $step['name']));
            $section->writeln('');

            $command = $app->find($step['name']);
            $commandInput = $step['input']();
            $commandInput->setInteractive(false);

            // Sub-command keeps its own progress bar, rendered inside the section
            $code = $command->run($commandInput, $section);

            if ($code !== Command::SUCCESS) {
                $global->finish();
                $io->newLine(2);
                $io->error(sprintf('Pipeline failed on step "%s" (%s).', $step['label'], $step['name']));
                return $code;
            }

            $section->writeln('');
            $section->writeln(sprintf('<info>✓ Done: %s</info>', $step['label']));
            $section->writeln('');

            $global->advance();
        }

        $global->setMessage('Done');
        $global->finish();

        $io->newLine(2);
        $io->success('Full pipeline completed successfully.');

        return Command::SUCCESS;
    }
}