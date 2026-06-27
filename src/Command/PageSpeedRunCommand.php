<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Service\Seo\PageSpeed\PageSpeedClient;
use App\Service\Seo\PageSpeed\PageSpeedQueue;
use App\Service\Seo\PageSpeed\PageSpeedRecorder;
use App\Service\Seo\PageSpeed\QuotaGuard;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PageSpeedRunCommand.
 *
 * Drains the PageSpeed queue filled by the admin dashboard. Runs the long Google
 * Lighthouse calls here (cron context) instead of on a web request, so the shared
 * FPM pool is never held behind Varnish (no "Backend fetch failed"). One job per
 * run by default bounds the per-tick wall-time.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:pagespeed:run', description: 'Run queued PageSpeed measurements off the web worker pool.')]
final class PageSpeedRunCommand extends Command
{
    public function __construct(
        private readonly PageSpeedQueue $queue,
        private readonly PageSpeedClient $client,
        private readonly PageSpeedRecorder $recorder,
        private readonly QuotaGuard $quota,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many queued measurements to process this run.', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->client->isEnabled()) {
            return Command::SUCCESS;
        }

        $lock = $this->queue->acquire();
        if (null === $lock) {
            return Command::SUCCESS;
        }

        try {
            $processed = 0;
            foreach ($this->queue->pending((int) $input->getOption('limit')) as $path => $job) {
                if (!$this->quota->canMeasure()) {
                    break;
                }

                try {
                    $website = $this->entityManager->getRepository(Website::class)->find($job['websiteId'] ?? 0);
                    if ($website instanceof Website && isset($job['publicUrl'])) {
                        $report = $this->client->measure((string) $job['publicUrl'], $job['locale'] ?? null);
                        $this->quota->consumeMeasurement();
                        $this->recorder->record($website, $job['code'] ?? null, $job['locale'] ?? null, $report);
                        ++$processed;
                    }
                } catch (\Throwable $exception) {
                    $this->logger->error('PageSpeed queued measurement failed', ['exception' => $exception, 'url' => $job['publicUrl'] ?? null]);
                } finally {
                    $this->queue->remove($path);
                }
            }

            $output->writeln(sprintf('PageSpeed: %d measurement(s) processed.', $processed));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return Command::SUCCESS;
    }
}
