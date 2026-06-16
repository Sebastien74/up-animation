<?php

declare(strict_types=1);

namespace App\Service\Seo\PageSpeed;

use App\Repository\Seo\PageSpeedQuotaRepository;

/**
 * QuotaGuard.
 *
 * Tracks daily Google PageSpeed Insights API usage against the configured daily quota.
 * One page measurement consumes one request per strategy (mobile, desktop), so quota is
 * expressed both in raw API requests and in whole measurements (pages).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class QuotaGuard
{
    public function __construct(
        private readonly PageSpeedQuotaRepository $repository,
        private readonly PageSpeedClient $client,
        private readonly int $dailyLimit,
    ) {
    }

    public function dailyLimit(): int
    {
        return max(0, $this->dailyLimit);
    }

    /**
     * API requests consumed by a single page measurement (one per strategy).
     */
    public function perMeasurement(): int
    {
        return max(1, count($this->client->strategies()));
    }

    public function usedToday(): int
    {
        return $this->repository->usedToday();
    }

    public function remainingRequests(): int
    {
        return max(0, $this->dailyLimit() - $this->usedToday());
    }

    /**
     * Whole measurements still allowed today within the quota.
     */
    public function remainingMeasurements(): int
    {
        return intdiv($this->remainingRequests(), $this->perMeasurement());
    }

    /**
     * Whether at least one more page can be measured today.
     */
    public function canMeasure(): bool
    {
        return $this->remainingRequests() >= $this->perMeasurement();
    }

    /**
     * Record the consumption of one page measurement.
     */
    public function consumeMeasurement(): void
    {
        $this->repository->consume($this->perMeasurement());
    }
}
