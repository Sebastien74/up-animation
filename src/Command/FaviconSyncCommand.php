<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Service\Core\FaviconMediaSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * FaviconSyncCommand.
 *
 * Aligns favicon medias on the current set for existing websites: drops
 * obsolete categories, provisions the new ones (multilingual) and refreshes
 * the kept files. Idempotent. Same source as the default medias fixtures.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:favicons:sync',
    description: 'Synchronize favicon medias on existing websites with the current set.',
)]
final class FaviconSyncCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FaviconMediaSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('website', null, InputOption::VALUE_REQUIRED, 'Restrict to a single website id')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Apply to every website')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report changes without writing anything');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $repository = $this->entityManager->getRepository(Website::class);
        if ($websiteId = $input->getOption('website')) {
            $website = $repository->find((int) $websiteId);
            $websites = $website ? [$website] : [];
        } elseif ($input->getOption('all')) {
            $websites = $repository->findAll();
        } else {
            $io->error('Specify --website=ID or --all.');

            return Command::INVALID;
        }

        if (!$websites) {
            $io->warning('No website matched.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $io->note('Dry-run: no change will be written.');
        }

        $deleted = $added = $refreshed = $deduped = 0;
        foreach ($websites as $website) {
            $result = $this->synchronizer->sync($website, $dryRun);
            $deleted += $result['deleted'];
            $added += $result['added'];
            $refreshed += $result['refreshed'];
            $deduped += $result['deduped'];
            $io->writeln(sprintf(
                '%s: %d obsolète(s) supprimée(s), %d ajouté(s), %d rafraîchi(s), %d doublon(s) supprimé(s).',
                (string) $website->getUploadDirname(),
                $result['deleted'],
                $result['added'],
                $result['refreshed'],
                $result['deduped'],
            ));
        }

        $io->success(sprintf('Terminé: -%d obsolètes, +%d, ~%d, -%d doublons.', $deleted, $added, $refreshed, $deduped));

        return Command::SUCCESS;
    }
}
