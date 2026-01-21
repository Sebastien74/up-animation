<?php

declare(strict_types=1);

namespace App\Service\Doctrine;

use Doctrine\DBAL\Platforms\MySQLPlatform as PlatformInterface;

/**
 * MySqlPlatform.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MySqlPlatform extends PlatformInterface
{
    /**
     * Returns the FOR UPDATE expression.
     */
    public function getForUpdateSQL(): string
    {
        return 'FOR UPDATE SKIP LOCKED';
    }

    /**
     * Returns the LISTEN expression for a given channel.
     */
    public function getListenSQL(string $channelName): string
    {
        return 'LISTEN "' . $channelName . '"';
    }

    /**
     * Returns the UNLISTEN expression for a given channel.
     */
    public function getUnlistenSQL(string $channelName): string
    {
        return 'UNLISTEN "' . $channelName . '"';
    }
}