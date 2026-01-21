<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\CommandHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * AppCommand.
 *
 * Helper to run CMS commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:cmd')]
class AppCommand extends Command
{
    private SymfonyStyle $io;
    private array $commands;

    /**
     * AppCommand constructor.
     */
    public function __construct(private readonly CommandHelper $commandHelper)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Helper to run CMS commands.')
            ->addArgument('alias', InputArgument::OPTIONAL, 'Alias of command you want to run.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if (!$this->commandHelper->isAllowed($this->io)) {
            return Command::FAILURE;
        }

        $this->commands = $this->commandHelper->setCommands();

        //        if ($this->runAlias($input, $output)) {
        //            return Command::FAILURE;
        //        }

        $this->runHelper($output);

        return Command::SUCCESS;
    }

    /**
     * Run alias command.
     *
     * @throws \Exception
     */
    private function runAlias(InputInterface $input, OutputInterface $output): int
    {
        $alias = $input->getArgument('alias');

        if (!empty($this->commands[$alias]['command']['website'])) {
            $this->io->error('You must run this command whit Helper!!!'.$alias);

            return Command::FAILURE;
        } elseif ($alias && !empty($this->commands[$alias]['command'])) {
            $this->commandHelper->runCmd($alias, $this->io, $output);

            return Command::SUCCESS;
        } elseif ($alias) {
            $this->io->error("This alias doesn't exist!!!");

            return Command::FAILURE;
        }

        return Command::FAILURE;
    }

    /**
     * Run commands Helper.
     *
     * @throws \Exception
     */
    private function runHelper(OutputInterface $output): void
    {
        $this->io->title('Welcome to CMS run commands Helper!!!');

        $this->setList();
        $alias = $this->setChoices();

        if (!empty($this->commands[$alias]['command']['website'])) {
            $website = $this->commandHelper->getWebsites($this->io);
            $this->commandHelper->commandArgument($alias, 'website', $website);
        }

        if (!empty($this->commands[$alias]['command']['symfony_style'])) {
            $this->commandHelper->commandArgument($alias, 'symfony_style', $this->io);
        }

        if (!empty($this->commands[$alias]['command']['output'])) {
            $this->commandHelper->commandArgument($alias, 'output', $output);
        }

        $this->commandHelper->runCmd($alias, $this->io, $output);
    }

    /**
     * Set list of commands helper.
     */
    private function setList(): void
    {
        $items = [];
        foreach ($this->commands as $alias => $configuration) {
            $items[] = [$alias, $this->commands[$alias]['command']['command'], $configuration['description']];
        }
        $this->io->table(['Alias', 'Command run', 'Description'], $items);
    }

    /**
     * Set choices select of commands.
     */
    private function setChoices(): string
    {
        $choices = [];
        foreach ($this->commands as $alias => $configuration) {
            $choices[] = $alias;
        }

        return $this->io->choice('Select command', $choices);
    }
}
