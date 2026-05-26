<?php

declare(strict_types=1);

namespace App\Service\Analytics;

/**
 * BotDetector.
 *
 * Cheap regex-based filter to drop crawler traffic before
 * it reaches the analytics queue. Not a security boundary:
 * sophisticated bots will pass through, but this removes
 * the bulk of noise (Googlebot, Bingbot, headless browsers,
 * uptime monitors, social previewers).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class BotDetector
{
    private const string BOT_PATTERN = '/(bot|crawler|spider|scraper|slurp|crawling|facebookexternalhit|preview|fetcher|monitor|pingdom|uptimerobot|headlesschrome|phantomjs|puppeteer|playwright|wget|curl|httpclient|python-requests)/i';

    public function isBot(?string $userAgent): bool
    {
        if (null === $userAgent || '' === $userAgent) {
            return true;
        }

        return 1 === preg_match(self::BOT_PATTERN, $userAgent);
    }
}
