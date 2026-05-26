<?php

declare(strict_types=1);

namespace App\MessageHandler\Analytics;

use App\Message\Analytics\RollupRequestMessage;
use App\Service\Analytics\AnalyticsRollupService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * RollupRequestHandler.
 *
 * Runs the throttled rollup off the user-facing request path.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessageHandler]
final readonly class RollupRequestHandler
{
    public function __construct(private AnalyticsRollupService $rollupService)
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(RollupRequestMessage $message): void
    {
        $this->rollupService->runThrottled(60);
    }
}
