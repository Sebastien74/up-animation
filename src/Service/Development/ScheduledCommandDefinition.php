<?php

declare(strict_types=1);

namespace App\Service\Development;

/**
 * ScheduledCommandDefinition.
 *
 * Immutable definition of a scheduled command, shared between fixtures
 * (new websites) and the retroactive installer (existing websites).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class ScheduledCommandDefinition
{
    public function __construct(
        public string $name,
        public string $command,
        public string $cronExpression,
        public string $description,
        public bool $active = false,
        public bool $installByDefault = false,
    ) {
    }
}
