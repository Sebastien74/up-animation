<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\ClearBundleInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AppClearBundleCommand.
 *
 * Run app vendor clear bundles
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:clear:bundle')]
class AppClearBundleCommand extends Command
{
    /**
     * AppClearBundleCommand constructor.
     */
    public function __construct(
        private readonly ClearBundleInterface $clearBundle,
        private readonly CoreLocatorInterface $coreLocator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To clear app vendor bundle.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('local' !== $this->coreLocator->envName()) {
            $output->writeln('<info>Command app:clear:bundle skipped (not in local environment).</info>');
            return Command::SUCCESS;
        }

        try {
            $this->clearBundle->execute();
            return Command::SUCCESS;
        } catch (\Exception $exception) {
            return Command::FAILURE;
        }
    }
}
