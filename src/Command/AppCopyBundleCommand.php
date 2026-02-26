<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Development\CopyBundleInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AppCopyBundleCommand.
 *
 * Run app vendor copy bundle
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'app:copy:bundle')]
class AppCopyBundleCommand extends Command
{
    public function __construct(
        private readonly CopyBundleInterface $copyBundle,
        private readonly CoreLocatorInterface $coreLocator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To copy app vendor bundle.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('local' !== $this->coreLocator->envName()) {
            $output->writeln('<info>Command app:copy:bundle skipped (not in local environment).</info>');
            return Command::SUCCESS;
        }

        try {
            $this->copyBundle->execute();
            return Command::SUCCESS;
        } catch (\Exception $exception) {
            return Command::FAILURE;
        }
    }
}