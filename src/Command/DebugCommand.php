<?php

declare(strict_types=1);

namespace App\Command;

/**
 * DebugCommand.
 *
 * To execute debug commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DebugCommand extends BaseCommand
{
    /**
     * Execute debug:{service}.
     */
    public function debug(string $service): string
    {
        return $this->execute([
            'command' => 'debug:'.$service,
        ]);
    }
}
