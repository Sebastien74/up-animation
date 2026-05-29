<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Entity\Api\FeedPost;
use App\Repository\Core\WebsiteRepository;
use DateTimeImmutable;

/**
 * FacebookFeedFetcher.
 *
 * Note: the Graph API exposes "full_picture" (a preview image, present even for
 * video posts) but not the raw video file. Only that picture is downloaded
 * locally; playback/reading is delegated to the permalink_url.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class FacebookFeedFetcher implements FeedFetcherInterface
{
    public function __construct(
        private FacebookService $facebookService,
        private WebsiteRepository $websiteRepository,
    ) {
    }

    public function provider(): string
    {
        return FeedPost::PROVIDER_FACEBOOK;
    }

    public function fetch(): array
    {
        $website = $this->websiteRepository->findCurrent();
        $model = $website?->api?->facebook;

        if (!$model || !$model->accessToken || !$model->pageId) {
            return [];
        }

        $raw = $this->facebookService->getFeed($model);
        $dtos = [];

        foreach ($raw as $item) {
            if (empty($item['id'])) {
                continue;
            }

            $picture = $item['full_picture'] ?? null;
            $isVideo = $this->isVideo($item);

            $dtos[] = new FeedPostDto(
                externalId: (string) $item['id'],
                permalink: $item['permalink_url'] ?? null,
                caption: $item['message'] ?? null,
                mediaType: $isVideo ? FeedPost::MEDIA_TYPE_VIDEO : FeedPost::MEDIA_TYPE_IMAGE,
                mediaUrl: $picture,
                thumbnailUrl: null,
                duration: null,
                publishedAt: isset($item['created_time']) ? new DateTimeImmutable($item['created_time']) : null,
                payload: $item,
            );
        }

        return $dtos;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function isVideo(array $item): bool
    {
        $type = $item['attachments']['data'][0]['type'] ?? '';

        return is_string($type) && str_contains($type, 'video');
    }
}
