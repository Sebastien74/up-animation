<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * CacheReclaimCommand.
 *
 * Weekly housekeeping: flushes the cache.app pool to reclaim the versioned
 * entries (fragments, page/layout result-cache keyed by cacheClearDate) that
 * have no TTL and are therefore never removed by cache:pool:prune. Mirrors
 * WebsiteCacheInvalidator: a direct pool clear, in-process, shared-hosting safe.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsCommand(
    name: 'app:cache:reclaim',
    description: 'Flush the cache.app pool to reclaim orphaned versioned entries (weekly disk housekeeping).',
)]
final class CacheReclaimCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $appCache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Reclaims disk at the cost of a lazy rebuild; scheduled off-peak, inactive by default.
        $this->appCache->clear();
        $io->success('cache.app pool flushed.');

        return Command::SUCCESS;
    }
}
