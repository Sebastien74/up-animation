<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Core\CronSchedulerService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * BaseCommand.
 *
 * Base commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BaseCommand
{
    /**
     * BaseCommand constructor.
     */
    public function __construct(
        protected readonly KernelInterface      $kernel,
        protected readonly CronSchedulerService $cronSchedulerService
    ) {
    }

    protected function execute(array $params): string
    {
        $command = !empty($params['command']) ? $params['command'] : null;
        $message = $command ? 'Command : '.$command.' ' : 'Command ';

        try {
            $application = new Application($this->kernel);
            $application->setAutoExit(false);
            $input = new ArrayInput($params);
            $output = new BufferedOutput();
            $application->run($input, $output);
            $this->cronSchedulerService->logger($message.'successfully executed.');
            return $output->fetch();
        } catch (\Exception $exception) {
            $this->cronSchedulerService->logger($message.$exception->getMessage().' - '.$exception->getTraceAsString(), null, false);
            return $exception->getMessage().' - '.$exception->getTraceAsString();
        }
    }
}
