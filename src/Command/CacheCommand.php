<?php

declare(strict_types=1);

namespace App\Command;

/**
 * CacheCommand.
 *
 * To execute cache commands
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CacheCommand extends BaseCommand
{
    /**
     * Execute cache:clear --env.
     */
    public function clear(bool $asFilesystem = false, bool $onlyRename = false): string
    {
        // Always clear via cache:clear: it clears AND warms (a fresh kernel recompiles the DI
        // container into the new cache dir before swapping it in), so the next web request finds a
        // warm cache. The former filesystem-rename fast path left that recompile to the next
        // request, whose cold synchronous rebuild exceeded Varnish's backend timeout -> 503.
        return $this->execute([
            'command' => 'cache:clear',
            '--env' => $this->kernel->getEnvironment(),
        ]);
    }

    /**
     * Execute cache:pool:clear for a single pool.
     */
    public function clearPool(string $pool): string
    {
        return $this->execute([
            'command' => 'cache:pool:clear',
            'pools' => [$pool],
        ]);
    }

    /**
     * Execute cache:pool:clear --all.
     */
    public function clearAllPools(): string
    {
        return $this->execute([
            'command' => 'cache:pool:clear',
            'pools' => [],
            '--all' => true,
        ]);
    }
}
