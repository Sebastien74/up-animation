<?php

declare(strict_types=1);

namespace App\Command;

/**
 * DoctrineCommand.
 *
 * To execute doctrine commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class DoctrineCommand extends BaseCommand
{
    /**
     * Execute doctrine:schema:update.
     */
    public function update(): string
    {
        return $this->execute([
            'command' => 'doctrine:schema:update',
            '--force' => true,
        ]);
    }

    /**
     * Execute doctrine:cache:clear-result.
     */
    public function cacheClearResult(): string
    {
        $this->execute(['command' => 'doctrine:cache:clear-metadata']);
        $this->execute(['command' => 'doctrine:cache:clear-query']);
        return $this->execute(['command' => 'doctrine:cache:clear-result']);
    }

    /**
     * Execute cache:pool:clear.
     */
    public function cacheClearPool(): void
    {
        $this->execute(['command' => 'cache:pool:clear', 'pools' => ['cache.global_clearer']]);
    }

    /**
     * Execute doctrine:schema:validate.
     */
    public function validate(): string
    {
        return $this->execute(['command' => 'doctrine:schema:validate']);
    }

    /**
     * Execute doctrine:fixtures:load.
     */
    public function fixtures(): string
    {
        return $this->execute([
            'command' => 'doctrine:fixtures:load',
            '--no-interaction' => true,
        ]);
    }
}
