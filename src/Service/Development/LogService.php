<?php

declare(strict_types=1);

namespace App\Service\Development;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * LogService.
 *
 * To copy file from path to other path
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LogService
{
    /**
     * LogService constructor.
     */
    public function __construct(private readonly string $logDir)
    {
    }

    /**
     * Log.
     */
    public function log(string $name, Level $level, string $filename, string $message): void
    {
        $logger = new Logger($name);
        $logger->pushHandler(new RotatingFileHandler($this->logDir.'/'.$filename, 10, $level));
        if (Level::Critical === $level) {
            $logger->critical($message);
        } elseif (Level::Warning === $level) {
            $logger->warning($message);
        } else {
            $logger->info($message);
        }
    }
}
