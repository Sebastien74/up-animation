<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Core\CronSchedulerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CronCommand.
 *
 * Run all commands scheduled
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(name: 'scheduler:execute')]
class CronCommand extends Command
{
    /**
     * CronCommand constructor.
     */
    public function __construct(private readonly CronSchedulerService $cronSchedulerService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Execute scheduled commands')
            ->setHelp('This class is the entry point to execute all scheduled command');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->cronSchedulerService->execute($input, $output);

            return Command::SUCCESS;
        } catch (\Exception $exception) {
            return Command::FAILURE;
        }
    }
}
