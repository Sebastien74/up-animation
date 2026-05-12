<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Entity\Api\FeedPost;
use App\Repository\Core\WebsiteRepository;
use App\Service\Content\TikTokService;
use DateTimeImmutable;

/**
 * TikTokFeedFetcher.
 *
 * Note: the TikTok Display API exposes the cover image (cover_image_url)
 * but not the raw video file. Only the cover is downloaded locally;
 * playback is delegated to the share_url permalink.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class TikTokFeedFetcher implements FeedFetcherInterface
{
    public function __construct(
        private TikTokService $tiktokService,
        private WebsiteRepository $websiteRepository,
    ) {
    }

    public function provider(): string
    {
        return FeedPost::PROVIDER_TIKTOK;
    }

    public function fetch(): array
    {
        $website = $this->websiteRepository->findCurrent();
        $model = $website?->api?->tiktok;

        if (!$model || !$model->accessToken) {
            return [];
        }

        $raw = $this->tiktokService->getFeed($model);
        $dtos = [];

        foreach ($raw as $item) {
            if (empty($item['id'])) {
                continue;
            }

            $publishedAt = null;
            if (!empty($item['create_time'])) {
                $publishedAt = (new DateTimeImmutable())->setTimestamp((int) $item['create_time']);
            }

            $dtos[] = new FeedPostDto(
                externalId: (string) $item['id'],
                permalink: $item['share_url'] ?? null,
                caption: $item['video_description'] ?? ($item['title'] ?? null),
                mediaType: FeedPost::MEDIA_TYPE_VIDEO,
                mediaUrl: null,
                thumbnailUrl: $item['cover_image_url'] ?? null,
                duration: isset($item['duration']) ? (int) $item['duration'] : null,
                publishedAt: $publishedAt,
                payload: $item,
            );
        }

        return $dtos;
    }
}
