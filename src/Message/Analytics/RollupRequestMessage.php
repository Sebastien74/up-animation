<?php

declare(strict_types=1);

namespace App\Message\Analytics;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * RollupRequestMessage.
 *
 * Signals that someone has opened the stats page. The async handler
 * runs a throttled rollup so the user-facing request never waits on it.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessage('async')]
final readonly class RollupRequestMessage
{
}
