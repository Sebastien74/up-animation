<?php

declare(strict_types=1);

namespace App\Command;

use App\Security\PasswordExpire;
use App\Service\Core\CronSchedulerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SecurityPasswordExpireCommand.
 *
 * Check if users passwords expire and send email
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'security:password:expire')]
class SecurityPasswordExpireCommand extends Command
{
    /**
     * SecurityPasswordExpireCommand constructor.
     */
    public function __construct(
        private readonly PasswordExpire $passwordExpire,
        private readonly CronSchedulerService $cronSchedulerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Check if users passwords expire and send email.')
            ->addArgument('cronLogger', InputArgument::OPTIONAL, 'Cron scheduler Logger')
            ->addArgument('commandLogger', InputArgument::OPTIONAL, 'Command Logger');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $input->getArgument('command');
        try {
            $this->passwordExpire->execute($input, $command);
            $this->cronSchedulerService->logger($command.' successfully executed.');

            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $this->cronSchedulerService->logger($command.' '.$exception->getMessage().' - '.$exception->getTraceAsString(), null, false);

            return Command::FAILURE;
        }
    }
}
