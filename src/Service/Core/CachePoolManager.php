<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Command\CacheCommand;
use Symfony\Component\Finder\Finder;

/**
 * CachePoolManager.
 *
 * Lists the configured Symfony cache pools and clears them in-process.
 * Filesystem adapters do not expose their keys nor a reliable name to directory
 * mapping (hashed sub-directories), so granularity stays at the pool level.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class CachePoolManager
{
    public const string ALL = '__all__';

    /**
     * Canonical pool list (mirrors `cache:pool:list`) with human labels.
     *
     * @var array<string, string>
     */
    private const array POOLS = [
        'cache.app' => 'Cache applicatif',
        'cache.system' => 'Cache système',
        'cache.validator' => 'Validation',
        'cache.serializer' => 'Sérialisation',
        'cache.property_info' => 'Property info',
        'cache.messenger.restart_workers_signal' => 'Messenger (restart workers)',
        'cache.http_client.pool' => 'Client HTTP',
        'cache.rate_limiter' => 'Rate limiter',
        'doctrine.query_cache_pool' => 'Doctrine (query)',
        'doctrine.result_cache_pool' => 'Doctrine (result)',
        'doctrine.system_cache_pool' => 'Doctrine (metadata)',
        'cache.validator_expression_language' => 'Validation (expressions)',
        'cache.webpack_encore' => 'Webpack Encore',
        'cache.security_expression_language' => 'Sécurité (expressions)',
        'cache.security_is_granted_attribute_expression_language' => 'Sécurité (isGranted)',
        'cache.security_is_csrf_token_valid_attribute_expression_language' => 'Sécurité (CSRF)',
        'cache.security_token_verifier' => 'Sécurité (token verifier)',
        'twig.cache' => 'Templates Twig',
        'cache.ux.twig_component' => 'Twig components (UX)',
    ];

    public function __construct(
        private readonly CacheCommand $cacheCommand,
        private readonly string $cacheDir,
    ) {
    }

    /**
     * @return list<array{name: string, label: string}>
     */
    public function listPools(): array
    {
        $pools = [];
        foreach (self::POOLS as $name => $label) {
            $pools[] = ['name' => $name, 'label' => $label];
        }

        return $pools;
    }

    /**
     * Aggregate disk usage of the whole cache directory (reliable, unlike per-pool).
     *
     * @return array{sizeBytes: int, fileCount: int}
     */
    public function getUsage(): array
    {
        if (!is_dir($this->cacheDir)) {
            return ['sizeBytes' => 0, 'fileCount' => 0];
        }

        $sizeBytes = 0;
        $fileCount = 0;
        foreach (Finder::create()->files()->in($this->cacheDir)->ignoreUnreadableDirs() as $file) {
            $sizeBytes += $file->getSize();
            ++$fileCount;
        }

        return ['sizeBytes' => $sizeBytes, 'fileCount' => $fileCount];
    }

    public function isKnownPool(string $pool): bool
    {
        return isset(self::POOLS[$pool]);
    }

    public function clearPool(string $pool): string
    {
        if (!$this->isKnownPool($pool)) {
            throw new \InvalidArgumentException(sprintf('Unknown cache pool "%s".', $pool));
        }

        return $this->cacheCommand->clearPool($pool);
    }

    public function clearAll(): string
    {
        return $this->cacheCommand->clearAllPools();
    }
}
