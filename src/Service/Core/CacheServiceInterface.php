<?php

declare(strict_types=1);

namespace App\Service\Core;

/**
 * CacheServiceInterface.
 *
 * Manage app cache.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CacheServiceInterface
{
    public function cachePool(mixed $entity, string $name, string $method, mixed $response = null, mixed $parentEntity = null): mixed;
    public function clearCaches(mixed $entity = null, bool $force = false): void;
    public function cacheKey(mixed $entity, ?string $prefix = null, bool $generateEmpty = true): ?string;
    public function generateRoutes(): void;
}
