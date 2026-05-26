<?php

declare(strict_types=1);

namespace App\Service\Analytics;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * SaltRotator.
 *
 * Produces an anonymous session hash from raw IP + User-Agent
 * using a salt rotated every day at midnight (Europe/Paris).
 *
 * Cross-day rotation is intentional: it prevents long-term
 * re-identification, aligning with CNIL guidance for analytics
 * exempted from consent.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class SaltRotator
{
    private const string CACHE_PREFIX = 'analytics.salt.';
    private const int SALT_BYTES = 32;
    private const int SALT_TTL_SECONDS = 90000;
    private const int HASH_BYTES = 16;

    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hashSession(string $ip, string $userAgent): string
    {
        if ('' === $ip || '' === $userAgent) {
            throw new \InvalidArgumentException('Cannot hash session: ip and user-agent must be provided.');
        }

        $salt = $this->currentSalt();
        $raw = hash('sha256', $salt.$ip.$userAgent, true);

        return bin2hex(substr($raw, 0, self::HASH_BYTES));
    }

    /**
     * @throws InvalidArgumentException
     */
    private function currentSalt(): string
    {
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
        $key = self::CACHE_PREFIX.$today;

        return $this->cache->get($key, static function (ItemInterface $item): string {
            $item->expiresAfter(self::SALT_TTL_SECONDS);

            return bin2hex(random_bytes(self::SALT_BYTES));
        });
    }
}
