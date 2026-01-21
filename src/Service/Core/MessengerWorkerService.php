<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * MessengerWorkerService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MessengerWorkerService
{
    private const string CONSUME_COMMAND = 'messenger:consume';
    private const string STOP_WORKER_COMMAND = 'messenger:stop-worker';
    private const bool ASYNCHRONOUS = false;
    private string $phpExecutable = 'php';

    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly KernelInterface $kernel,
    ) {

    }

    /**
     * Get php executable
     *
     * @throws Exception
     */
    private function setPHPExecutable(): void
    {
        $phpFinder = new PhpExecutableFinder;
        $this->phpExecutable = $phpFinder->find();

//        // Automatically detect the PHP executable (depending on the system)
//        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
//            // On Windows, attempt to locate PHP via the PHP_BINARY environment variable or PATH
//            $phpExecutable = getenv('PHP_BINARY');
//            if (!$phpExecutable) {
//                // If PHP_BINARY is not set, use the `where` command to locate PHP
//                $phpExecutable = shell_exec('where php');
//            }
//            // Check if the PHP path is valid
//            if (!$phpExecutable || !file_exists(trim($phpExecutable))) {
//                // If not found, throw an exception or set an alternative path if necessary
//                throw new \RuntimeException('PHP executable not found');
//            }
//            $phpExecutable = trim($phpExecutable); // Clean the path
//        } else {
//            // On UNIX-like systems, use `which php` to locate PHP
//            $phpExecutable = shell_exec('which php');
//            if (!$phpExecutable) {
//                throw new \RuntimeException('PHP executable not found');
//            }
//            $phpExecutable = trim($phpExecutable);
//        }
    }

    /**
     * Start and stop the Messenger worker in a background process.
     *
     * @throws Exception
     */
    public function workerInBackground(string $action = 'consume', string $queue = 'async', int $memoryLimit = 128): void
    {
        $this->setPHPExecutable();
        $this->executeShellCommand($action, $queue, $memoryLimit);
    }

    private function executeShellCommand(string $action = 'consume', string $queue = 'async', int $memoryLimit = 128): void
    {
        // Build the command to start the worker
        $strCommand = 'consume' === $action ? self::CONSUME_COMMAND : self::STOP_WORKER_COMMAND;
        $command = $this->getCommand($action, $queue, $memoryLimit);

        // Set up the process descriptor for stdin, stdout, and stderr
        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin, peut être nécessaire si la commande attend une entrée
            1 => ['file', 'NUL', 'w'], // stdout, redirigé vers "NUL" (équivalent à /dev/null sous Windows)
            2 => ['file', 'NUL', 'w'], // stderr, redirigé également
        ];
        // Execute the command in the background
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // For Windows, use proc_open with start /B
            $process = proc_open('start /B ' . $command, $descriptorSpec, $pipes);
        } else {
            // For UNIX-like systems, execute in the background with proc_open
            $process = proc_open($command . ' > /dev/null 2>&1 &', $descriptorSpec, $pipes);
        }

        if (is_resource($process)) {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
        }

        // Close the pipes and process
        if (is_resource($process) && 'consume' === $strCommand) {
            $this->executeShellCommand('stop-worker', $queue, $memoryLimit);
        }
    }

    private function getCommand(string $action = 'consume', string $queue = 'async', int $memoryLimit = 128): string
    {
        $binDirname = $this->coreLocator->projectDir().'/bin/console';
        $binDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $binDirname);

        switch ($action) {
            case 'consume':
                $strCommand = self::CONSUME_COMMAND;
                $command = sprintf(
                    '%s %s %s %s --memory-limit=%dM --no-interaction',
                    escapeshellcmd($this->phpExecutable), // Escaper le chemin de l'exécutable PHP
                    escapeshellarg($binDirname), // Escaper le chemin vers bin/console
                    $strCommand,
                    $queue, // Escaper le nom de la queue
                    $memoryLimit // Limite mémoire
                );
                break;
            case 'stop-worker':
                $strCommand = self::STOP_WORKER_COMMAND;
                $command = sprintf(
                    '%s %s %s',
                    escapeshellcmd($this->phpExecutable), // Escaper le chemin de l'exécutable PHP
                    escapeshellarg($binDirname), // Escaper le chemin vers bin/console
                    $strCommand
                );
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Action "%s" non reconnue.', $action));
        }

        return $command;
    }
}
