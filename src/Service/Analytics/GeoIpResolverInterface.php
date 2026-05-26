<?php

declare(strict_types=1);

namespace App\Service\Analytics;

/**
 * GeoIpResolverInterface.
 *
 * Resolves a country code from a raw IP address using a strictly
 * offline lookup (embedded database). Implementations must never
 * call an external service nor persist the IP.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface GeoIpResolverInterface
{
    public function resolveCountry(string $ip): ?string;
}
