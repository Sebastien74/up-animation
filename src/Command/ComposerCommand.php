<?php

declare(strict_types=1);

namespace App\Command;

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * ComposerCommand.
 *
 * To execute composer commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ComposerCommand
{
    /**
     * ComposerCommand constructor.
     */
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Execute cache:clear --env.
     *
     * @throws \Exception
     */
    public function autoload(): string
    {
        try {
            $bootstrapDirname = '/composer.phar/src/bootstrap.php';
            $bootstrapDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $bootstrapDirname);
            require_once 'phar://'.$this->projectDir.$bootstrapDirname;

            putenv("COMPOSER_HOME={$this->projectDir}");
            putenv('OSTYPE=OS400'); // force to use php://output instead of php://stdout

            $application = new Application();
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'dump-autoload',
                '--no-dev' => true,
                '--classmap-authoritative' => true,
            ]);
            $application->run($input);

            return '';
            //            return $output->fetch();
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
