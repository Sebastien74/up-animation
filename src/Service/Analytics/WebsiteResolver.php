<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * WebsiteResolver.
 *
 * Lightweight host -> website id lookup for the hot ingestion path.
 * Bypasses WebsiteRepository::findOneByHost on purpose: that method
 * eagerly hydrates ~10 joined relations which is wasteful when we
 * only need the integer id.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class WebsiteResolver
{
    private const string CACHE_PREFIX = 'analytics.website_id.';
    private const int CACHE_TTL = 3600;

    public function __construct(
        private Connection $connection,
        private CacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resolve(string $host): ?int
    {
        $host = strtolower(trim($host));
        if ('' === $host) {
            return null;
        }

        $key = self::CACHE_PREFIX.hash('xxh128', $host);

        $cached = $this->cache->get($key, function (ItemInterface $item) use ($host): int {
            $item->expiresAfter(self::CACHE_TTL);
            try {
                $id = $this->connection->fetchOne(
                    'SELECT w.id FROM upa_core_website w '
                    .'INNER JOIN upa_core_configuration c ON w.configuration_id = c.id '
                    .'INNER JOIN upa_core_domain d ON d.configuration_id = c.id '
                    .'WHERE d.name = :host AND w.active = 1 AND c.enableAnalytics = 1 LIMIT 1',
                    ['host' => $host]
                );
            } catch (DBALException) {
                return 0;
            }

            return false === $id ? 0 : (int) $id;
        });

        return 0 === $cached ? null : $cached;
    }
}
