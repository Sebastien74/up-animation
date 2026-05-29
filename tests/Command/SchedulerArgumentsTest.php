<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Service\Development\ScheduledCommandCatalog;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Regression guard for the scheduler argument injection (bug C).
 *
 * CronSchedulerService injects cronLogger/commandLogger only when the target
 * command declares them. This test rebuilds that exact input for every catalog
 * command and asserts it binds without error, and that a command which does not
 * declare those arguments would reject them.
 */
final class SchedulerArgumentsTest extends KernelTestCase
{
    public function testCatalogCommandsAcceptSchedulerInput(): void
    {
        $application = new Application(self::bootKernel());
        $catalog = new ScheduledCommandCatalog();

        foreach ($catalog->all() as $definition) {
            $command = $application->find($definition->command);
            $command->mergeApplicationDefinition();
            $inputDefinition = $command->getDefinition();

            $parameters = ['command' => $definition->command];
            if ($inputDefinition->hasArgument('cronLogger')) {
                $parameters['cronLogger'] = 'cron-scheduler.log';
            }
            if ($inputDefinition->hasArgument('commandLogger')) {
                $parameters['commandLogger'] = $definition->command.'.log';
            }

            $input = new ArrayInput($parameters);
            $input->bind($inputDefinition);
            $input->validate();
            $this->addToAssertionCount(1);
        }
    }

    public function testCommandWithoutLoggerArgsRejectsThem(): void
    {
        $application = new Application(self::bootKernel());
        $command = $application->find('app:analytics:rollup');
        $command->mergeApplicationDefinition();

        $this->expectException(InvalidArgumentException::class);

        $input = new ArrayInput(['command' => 'app:analytics:rollup', 'cronLogger' => 'x.log']);
        $input->bind($command->getDefinition());
    }
}
