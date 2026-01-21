<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\CommandHelper;
use App\Service\Development\EntityService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * EntityCommand.
 *
 * Helper to run CMS commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:cmd:entities')]
class EntityCommand extends Command
{
    /**
     * EntityCommand constructor.
     */
    public function __construct(
        private readonly EntityService $entityService,
        private readonly CommandHelper $commandHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To regenerate entities configuration.')
            ->addArgument('website', InputArgument::OPTIONAL, 'WebsiteModel entity.')
            ->addArgument('symfony_style', InputArgument::OPTIONAL, 'SymfonyStyle entity.')
            ->addArgument('output', InputArgument::OPTIONAL, 'Command output.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->commandHelper->getIo($input, $output);

        if (!$this->commandHelper->isAllowed($io)) {
            return Command::FAILURE;
        }

        $output = $this->commandHelper->getOutput($input, $output);
        $website = $this->commandHelper->getWebsite($input, $io);

        if ($website) {
            $output->writeln('<comment>Regeneration progressing...</comment>');
            $io->newLine();
            $this->entityService->execute($website, $website->getConfiguration()->getLocale());
            $io->block('Entities successfully regenerated.', 'RUN', 'fg=black;bg=green', ' ', true);
        }

        return Command::SUCCESS;
    }
}
