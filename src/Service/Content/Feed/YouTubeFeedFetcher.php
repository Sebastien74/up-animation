<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Entity\Api\FeedPost;
use App\Repository\Core\WebsiteRepository;
use DateTimeImmutable;

/**
 * YouTubeFeedFetcher.
 *
 * Note: the YouTube Data API search endpoint returns the thumbnail and metadata
 * but not the raw video. Only the thumbnail is downloaded locally; playback is
 * delegated to the watch URL permalink.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class YouTubeFeedFetcher implements FeedFetcherInterface
{
    private const string WATCH_URL = 'https://www.youtube.com/watch?v=%s';

    public function __construct(
        private YouTubeService $youTubeService,
        private WebsiteRepository $websiteRepository,
    ) {
    }

    public function provider(): string
    {
        return FeedPost::PROVIDER_YOUTUBE;
    }

    public function fetch(): array
    {
        $website = $this->websiteRepository->findCurrent();
        $model = $website?->api?->google;

        if (!$model || !$model->youtubeApiKey || !$model->youtubeChannelId) {
            return [];
        }

        $raw = $this->youTubeService->getVideos($model);
        $dtos = [];

        foreach ($raw as $item) {
            $videoId = $item['id']['videoId'] ?? null;
            if (!$videoId) {
                continue;
            }

            $snippet = $item['snippet'] ?? [];

            $dtos[] = new FeedPostDto(
                externalId: (string) $videoId,
                permalink: sprintf(self::WATCH_URL, $videoId),
                caption: $snippet['title'] ?? null,
                mediaType: FeedPost::MEDIA_TYPE_VIDEO,
                mediaUrl: null,
                thumbnailUrl: $this->bestThumbnail($snippet['thumbnails'] ?? []),
                duration: null,
                publishedAt: isset($snippet['publishedAt']) ? new DateTimeImmutable($snippet['publishedAt']) : null,
                payload: $item,
            );
        }

        return $dtos;
    }

    /**
     * Pick the highest-resolution thumbnail available.
     *
     * @param array<string, array{url?: string}> $thumbnails
     */
    private function bestThumbnail(array $thumbnails): ?string
    {
        foreach (['maxres', 'standard', 'high', 'medium', 'default'] as $size) {
            if (!empty($thumbnails[$size]['url'])) {
                return $thumbnails[$size]['url'];
            }
        }

        return null;
    }
}
