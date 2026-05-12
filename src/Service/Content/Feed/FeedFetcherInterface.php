<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * FeedFetcherInterface.
 *
 * Implementations fetch the latest posts from a social provider
 * (Instagram, TikTok, ...) and normalize them into FeedPostDto[].
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AutoconfigureTag('app.feed_fetcher')]
interface FeedFetcherInterface
{
    /**
     * Provider identifier (matches FeedPost::PROVIDER_* constants).
     */
    public function provider(): string;

    /**
     * @return FeedPostDto[]
     */
    public function fetch(): array;
}
