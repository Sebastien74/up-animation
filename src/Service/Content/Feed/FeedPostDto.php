<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use DateTimeImmutable;

/**
 * FeedPostDto.
 *
 * Provider-agnostic normalized representation of a feed post,
 * produced by a FeedFetcherInterface and consumed by FeedSyncService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class FeedPostDto
{
    public function __construct(
        public string $externalId,
        public ?string $permalink = null,
        public ?string $caption = null,
        public ?string $mediaType = null,
        public ?string $mediaUrl = null,
        public ?string $thumbnailUrl = null,
        public ?int $duration = null,
        public ?DateTimeImmutable $publishedAt = null,
        public array $payload = [],
    ) {
    }
}
