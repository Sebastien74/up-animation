<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * InvalidateCacheItems.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessage('async')]
final readonly class InvalidateCacheItems
{
    public function __construct(
        public array $cacheKeys,
    ) {
    }
}
