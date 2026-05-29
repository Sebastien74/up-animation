<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\ScheduledCommand;
use App\Entity\Core\Website;
use App\Service\Core\Urlizer;
use App\Service\Development\ScheduledCommandCatalog;
use App\Service\Development\ScheduledCommandDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * SchedulerInstallCommand.
 *
 * Retroactively installs the scheduled commands on every website that
 * doesn't already have them. Definitions come from ScheduledCommandCatalog,
 * the same source used by the fixtures for new websites. Safe to run
 * multiple times: each (website, command) pair is checked first.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:scheduler:install',
    description: 'Install scheduled commands on every website that lacks them.',
    aliases: ['app:analytics:install-scheduler'],
)]
final class SchedulerInstallCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ScheduledCommandCatalog $catalog,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('website', null, InputOption::VALUE_REQUIRED, 'Restrict to a single website id')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Install every defined command, not only the active defaults')
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

        $definitions = $input->getOption('all') ? $this->catalog->all() : $this->catalog->defaults();
        // --disabled forces inactive; otherwise each definition keeps its own default state.
        $forceActive = $input->getOption('disabled') ? false : null;

        $created = 0;
        $skipped = 0;

        foreach ($websites as $website) {
            foreach ($definitions as $definition) {
                $existing = $scheduledRepository->findOneBy([
                    'website' => $website,
                    'command' => $definition->command,
                ]);
                if (null !== $existing) {
                    ++$skipped;
                    continue;
                }

                $this->entityManager->persist($this->build($website, $definition, $forceActive));
                ++$created;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d scheduled command(s) created, %d already present.', $created, $skipped));

        return Command::SUCCESS;
    }

    private function build(Website $website, ScheduledCommandDefinition $definition, ?bool $forceActive): ScheduledCommand
    {
        return (new ScheduledCommand())
            ->setWebsite($website)
            ->setAdminName($definition->name)
            ->setCommand($definition->command)
            ->setCronExpression($definition->cronExpression)
            ->setDescription($definition->description)
            ->setLogFile(Urlizer::urlize($definition->command).'.log')
            ->setActive($forceActive ?? $definition->active);
    }
}
