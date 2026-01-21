<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Core\ScheduledCommandRepository;
use App\Service\Core\CronSchedulerService;
use App\Service\Core\GdprService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * GdprCommand.
 *
 * To run GDPR remove data commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'gdpr:remove')]
class GdprCommand extends Command
{
    /**
     * GdprCommand constructor.
     */
    public function __construct(
        private readonly GdprService $gdprService,
        private readonly ScheduledCommandRepository $commandRepository,
        private readonly CronSchedulerService $cronSchedulerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('GDPR remove data.')
            ->addArgument('cronLogger', InputArgument::OPTIONAL, 'Cron scheduler Logger')
            ->addArgument('commandLogger', InputArgument::OPTIONAL, 'Command Logger');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $command = $input->getArgument('command');
        $commands = $this->commandRepository->findBy(['command' => $command]);

        foreach ($commands as $command) {
            $commandName = !empty($command->getCommand()) ? $command->getCommand() : null;
            $message = $commandName ? 'Command : '.$commandName.' ' : 'Command ';
            try {
                $this->gdprService->removeData($command->getWebsite(), $input, $command);
                $this->cronSchedulerService->logger($message.'successfully executed.');
            } catch (\Exception $exception) {
                $this->cronSchedulerService->logger($message.$exception->getMessage().' - '.$exception->getTraceAsString(), null, false);
                continue;
            }
        }

        $message = 'GDPR data successfully deleted.';
        $io->block($message, 'OK', 'fg=black;bg=green', ' ', true);

        return Command::SUCCESS;
    }
}
