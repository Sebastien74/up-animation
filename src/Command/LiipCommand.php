<?php

declare(strict_types=1);

namespace App\Command;

/**
 * LiipCommand.
 *
 * To execute liip imagine commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LiipCommand extends BaseCommand
{
    /**
     * Execute liip:imagine:cache:remove.
     */
    public function remove(): string
    {
        return $this->execute([
            'command' => 'liip:imagine:cache:remove',
        ]);
    }
}
