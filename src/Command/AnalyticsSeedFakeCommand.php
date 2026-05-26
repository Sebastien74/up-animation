<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Analytics\AnalyticsRollupService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * AnalyticsSeedFakeCommand.
 *
 * Generates large volumes of fake analytics events to load-test the
 * ingestion, aggregation and rendering stack. Inserts via raw DBAL
 * multi-row INSERT for throughput; ORM hydration would be orders of
 * magnitude slower at this scale.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:analytics:seed-fake', description: 'Generate fake analytics events for load testing.')]
final class AnalyticsSeedFakeCommand extends Command
{
    private const string EVENT_TABLE = 'upa_analytics_event';
    private const string HOURLY_TABLE = 'upa_analytics_hourly';
    private const string DAILY_TABLE = 'upa_analytics_daily';
    private const int BATCH_SIZE = 2000;

    private const array URLS = [
        '/', '/contact', '/qui-sommes-nous', '/services', '/services/animation',
        '/services/communication', '/services/digital', '/services/evenementiel',
        '/blog', '/blog/article-1', '/blog/article-2', '/blog/article-3',
        '/blog/article-4', '/blog/article-5', '/realisations',
        '/realisations/projet-a', '/realisations/projet-b', '/realisations/projet-c',
        '/realisations/projet-d', '/recrutement', '/recrutement/offre-1',
        '/recrutement/offre-2', '/agences/paris', '/agences/lyon',
        '/agences/bordeaux', '/agences/marseille', '/mentions-legales',
        '/cgv', '/rgpd', '/contact?sujet=devis', '/newsletter', '/connexion',
    ];
    private const array DEVICES = ['desktop', 'desktop', 'desktop', 'mobile', 'mobile', 'mobile', 'mobile', 'tablet'];
    private const array BROWSERS = ['chrome', 'chrome', 'chrome', 'safari', 'safari', 'firefox', 'edge', 'opera', 'other'];
    private const array OS = ['windows', 'windows', 'macos', 'ios', 'ios', 'android', 'android', 'linux', 'other'];
    private const array COUNTRIES = ['FR', 'FR', 'FR', 'FR', 'FR', 'BE', 'BE', 'CH', 'CA', 'DE', 'ES', 'IT', 'GB', 'US', 'PT', 'NL', 'LU', 'MA', 'TN'];
    private const array REFERRERS = [null, null, null, null, 'google.com', 'google.com', 'google.com', 'bing.com', 'duckduckgo.com', 'linkedin.com', 'facebook.com', 'instagram.com', 'youtube.com', 'twitter.com', 'reddit.com'];
    private const array EVENT_TYPES = ['pageview', 'pageview', 'pageview', 'pageview', 'pageview', 'pageview', 'pageview', 'click', 'click', 'scroll', 'scroll', 'form'];
    private const array VIEWPORTS = ['360x640', '375x667', '414x896', '768x1024', '1024x768', '1280x720', '1366x768', '1440x900', '1536x864', '1920x1080'];
    private const array CLICK_TARGETS = [
        ['label' => 'Demander un devis', 'action' => 'navigation', 'tag' => 'a'],
        ['label' => 'Nous contacter', 'action' => 'navigation', 'tag' => 'a'],
        ['label' => 'Voir le projet', 'action' => 'navigation', 'tag' => 'a'],
        ['label' => 'Decouvrir nos services', 'action' => 'navigation', 'tag' => 'a'],
        ['label' => 'Telecharger la plaquette', 'action' => 'outbound', 'tag' => 'a'],
        ['label' => 'Voir l offre', 'action' => 'navigation', 'tag' => 'a'],
        ['label' => 'Envoyer un email', 'action' => 'mailto', 'tag' => 'a'],
        ['label' => 'Appeler le standard', 'action' => 'tel', 'tag' => 'a'],
        ['label' => 'Ouvrir la galerie', 'action' => 'modal', 'tag' => 'button'],
        ['label' => 'Voir la video', 'action' => 'modal', 'tag' => 'button'],
        ['label' => 'Ajouter aux favoris', 'action' => 'button', 'tag' => 'button'],
        ['label' => 'Partager', 'action' => 'dropdown', 'tag' => 'button'],
        ['label' => 'S inscrire a la newsletter', 'action' => 'submit', 'tag' => 'button'],
        ['label' => 'Postuler', 'action' => 'navigation', 'tag' => 'a'],
        ['label' => 'En savoir plus', 'action' => 'collapse', 'tag' => 'button'],
        ['label' => 'Voir tous les articles', 'action' => 'navigation', 'tag' => 'a'],
    ];
    private const array SCROLL_MILESTONES = [25, 50, 75, 100];
    private const array FORM_NAMES = ['contact', 'newsletter', 'devis', 'rappel', 'candidature'];

    public function __construct(
        private readonly Connection $connection,
        private readonly AnalyticsRollupService $rollupService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('events', null, InputOption::VALUE_REQUIRED, 'Number of events to generate', 500_000)
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'Spread events over the past N days', 60)
            ->addOption('website', null, InputOption::VALUE_REQUIRED, 'Website ID to seed (auto-detected if omitted)')
            ->addOption('locales', null, InputOption::VALUE_REQUIRED, 'Comma-separated locales', 'fr,en,es,it')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Truncate the 3 analytics tables before seeding')
            ->addOption('rollup', null, InputOption::VALUE_NONE, 'Run the rollup right after seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $total = max(1, (int) $input->getOption('events'));
        $days = max(1, (int) $input->getOption('days'));
        $websiteId = (int) ($input->getOption('website') ?? 0);
        if ($websiteId <= 0) {
            $websiteId = $this->autoDetectWebsiteId();
        }
        if (0 === $websiteId) {
            $io->error('No active website found. Pass --website=<id> explicitly.');

            return Command::FAILURE;
        }

        $locales = array_values(array_filter(array_map('trim', explode(',', (string) $input->getOption('locales')))));
        if ([] === $locales) {
            $locales = ['fr'];
        }
        $localeCount = count($locales);

        if ($input->getOption('clear')) {
            $io->writeln('Truncating analytics tables...');
            $this->connection->executeStatement('TRUNCATE TABLE '.self::EVENT_TABLE);
            $this->connection->executeStatement('TRUNCATE TABLE '.self::HOURLY_TABLE);
            $this->connection->executeStatement('TRUNCATE TABLE '.self::DAILY_TABLE);
        }

        $rangeSec = $days * 86400;
        $now = time();
        $startTs = $now - $rangeSec;
        $sessionPool = max(50, (int) ($total / max(1, $days) / 4));

        $started = microtime(true);
        $progress = $io->createProgressBar($total);
        $progress->setRedrawFrequency(max(500, (int) ($total / 200)));
        $progress->start();

        $rows = [];
        $inserted = 0;

        for ($i = 0; $i < $total; ++$i) {
            $eventTs = $startTs + mt_rand(0, $rangeSec);
            $dayOffset = (int) (($eventTs - $startTs) / 86400);
            $userIndex = mt_rand(1, $sessionPool);
            $sessionHash = substr(md5($userIndex.'.'.$dayOffset.'.'.$websiteId), 0, 32);
            $eventType = self::EVENT_TYPES[array_rand(self::EVENT_TYPES)];

            $rows[] = [
                date('Y-m-d H:i:s', $eventTs),
                $websiteId,
                $sessionHash,
                $eventType,
                self::URLS[array_rand(self::URLS)],
                self::REFERRERS[array_rand(self::REFERRERS)],
                self::COUNTRIES[array_rand(self::COUNTRIES)],
                self::DEVICES[array_rand(self::DEVICES)],
                self::BROWSERS[array_rand(self::BROWSERS)],
                self::OS[array_rand(self::OS)],
                $locales[mt_rand(0, $localeCount - 1)],
                self::VIEWPORTS[array_rand(self::VIEWPORTS)],
                $this->buildPayload($eventType),
            ];

            if (count($rows) >= self::BATCH_SIZE) {
                $inserted += $this->insertBatch($rows);
                $progress->advance(count($rows));
                $rows = [];
            }
        }
        if ([] !== $rows) {
            $inserted += $this->insertBatch($rows);
            $progress->advance(count($rows));
        }

        $progress->finish();
        $elapsed = microtime(true) - $started;
        $io->newLine(2);
        $io->success(sprintf('%d events inserted for website #%d in %.1fs (%.0f rows/s).', $inserted, $websiteId, $elapsed, $inserted / max(0.1, $elapsed)));

        if ($input->getOption('rollup')) {
            $io->writeln('Running rollup...');
            $rollStart = microtime(true);
            $result = $this->rollupService->run($days * 24, $days);
            $rollElapsed = microtime(true) - $rollStart;
            $io->writeln(sprintf('Rollup: %d hourly + %d daily buckets in %.1fs.', $result['hourly'], $result['daily'], $rollElapsed));
        } else {
            $io->note(sprintf('Run `php bin/console app:analytics:rollup --hours=%d --days=%d` to populate aggregates.', $days * 24, $days));
        }

        return Command::SUCCESS;
    }

    /**
     * @param list<list<mixed>> $rows
     */
    private function insertBatch(array $rows): int
    {
        $placeholders = array_fill(0, count($rows), '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $params = array_merge(...$rows);

        $sql = 'INSERT INTO '.self::EVENT_TABLE
            .' (occurredAt, websiteId, sessionHash, eventType, urlPath, referrerDomain, countryCode, device, browser, os, locale, viewport, eventPayload) VALUES '
            .implode(', ', $placeholders);

        return (int) $this->connection->executeStatement($sql, $params);
    }

    private function buildPayload(string $eventType): ?string
    {
        return match ($eventType) {
            'click' => $this->encodePayload(self::CLICK_TARGETS[array_rand(self::CLICK_TARGETS)]),
            'scroll' => $this->encodePayload(['milestone' => self::SCROLL_MILESTONES[array_rand(self::SCROLL_MILESTONES)]]),
            'form' => $this->encodePayload(['name' => self::FORM_NAMES[array_rand(self::FORM_NAMES)]]),
            default => null,
        };
    }

    /**
     * @param array<string, scalar|null> $payload
     */
    private function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    private function autoDetectWebsiteId(): int
    {
        try {
            $id = $this->connection->fetchOne('SELECT id FROM upa_core_website WHERE active = 1 ORDER BY id ASC LIMIT 1');

            return false === $id ? 0 : (int) $id;
        } catch (DBALException) {
            return 0;
        }
    }
}
