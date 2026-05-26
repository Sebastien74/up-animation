<?php

declare(strict_types=1);

namespace App\Service\Analytics;

/**
 * NullGeoIpResolver.
 *
 * Default no-op implementation: always returns null.
 * Keeps the ingestion pipeline working end-to-end before a real
 * geo provider (MaxMind GeoLite2 or equivalent) is wired in.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class NullGeoIpResolver implements GeoIpResolverInterface
{
    public function resolveCountry(string $ip): ?string
    {
        return null;
    }
}
