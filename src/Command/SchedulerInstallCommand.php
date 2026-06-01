<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Service\Development\ScheduledCommandInstaller;
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
        private readonly ScheduledCommandInstaller $installer,
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

        $website = null;
        if ($websiteId = $input->getOption('website')) {
            $website = $this->entityManager->getRepository(Website::class)->find((int) $websiteId);
            if (null === $website) {
                $io->warning('No website matched.');

                return Command::SUCCESS;
            }
        }

        // --disabled forces inactive; otherwise each definition keeps its own default state.
        $forceActive = $input->getOption('disabled') ? false : null;
        $created = $this->installer->installMissing($website, (bool) $input->getOption('all'), $forceActive);

        $io->success(sprintf('%d scheduled command(s) created.', $created));

        return Command::SUCCESS;
    }
}
