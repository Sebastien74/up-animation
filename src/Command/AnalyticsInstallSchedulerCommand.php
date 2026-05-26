<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\ScheduledCommand;
use App\Entity\Core\Website;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * AnalyticsInstallSchedulerCommand.
 *
 * Retroactively installs the analytics rollup and purge scheduled
 * commands on every website that doesn't already have them. Safe to
 * run multiple times: each (website, command) pair is checked first.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:analytics:install-scheduler', description: 'Install rollup and purge scheduled commands on every website that lacks them.')]
final class AnalyticsInstallSchedulerCommand extends Command
{
    private const array COMMANDS = [
        [
            'name' => 'Agrégation des statistiques',
            'command' => 'app:analytics:rollup',
            'expression' => '15 * * * *',
            'description' => 'Reconstruit les buckets horaires et journaliers à partir des événements bruts',
        ],
        [
            'name' => 'Purge des statistiques',
            'command' => 'app:analytics:purge',
            'expression' => '30 3 * * *',
            'description' => 'Supprime les événements bruts au-delà de la fenêtre de rétention',
        ],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('website', null, InputOption::VALUE_REQUIRED, 'Restrict to a single website id')
            ->addOption('disabled', null, InputOption::VALUE_NONE, 'Create the commands as disabled (admin must enable them manually)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $websiteRepository = $this->entityManager->getRepository(Website::class);
        $scheduledRepository = $this->entityManager->getRepository(ScheduledCommand::class);

        $criteria = [];
        if ($websiteId = $input->getOption('website')) {
            $criteria['id'] = (int) $websiteId;
        }
        $websites = $websiteRepository->findBy($criteria);
        if ([] === $websites) {
            $io->warning('No website matched.');

            return Command::SUCCESS;
        }

        $active = !$input->getOption('disabled');
        $created = 0;
        $skipped = 0;

        foreach ($websites as $website) {
            foreach (self::COMMANDS as $configuration) {
                $existing = $scheduledRepository->findOneBy([
                    'website' => $website,
                    'command' => $configuration['command'],
                ]);
                if (null !== $existing) {
                    ++$skipped;
                    continue;
                }

                $entity = (new ScheduledCommand())
                    ->setWebsite($website)
                    ->setAdminName($configuration['name'])
                    ->setCommand($configuration['command'])
                    ->setCronExpression($configuration['expression'])
                    ->setDescription($configuration['description'])
                    ->setLogFile(Urlizer::urlize($configuration['command']).'.log')
                    ->setActive($active);

                $this->entityManager->persist($entity);
                ++$created;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d scheduled command(s) created, %d already present.', $created, $skipped));

        return Command::SUCCESS;
    }
}
