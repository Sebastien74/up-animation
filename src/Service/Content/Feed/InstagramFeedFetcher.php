<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Entity\Api\FeedPost;
use App\Repository\Core\WebsiteRepository;
use App\Service\Content\InstagramService;
use DateTimeImmutable;

/**
 * InstagramFeedFetcher.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class InstagramFeedFetcher implements FeedFetcherInterface
{
    public function __construct(
        private InstagramService $instagramService,
        private WebsiteRepository $websiteRepository,
    ) {
    }

    public function provider(): string
    {
        return FeedPost::PROVIDER_INSTAGRAM;
    }

    public function fetch(): array
    {
        $website = $this->websiteRepository->findCurrent();
        $model = $website?->api?->instagram;

        if (!$model || !$model->accessToken) {
            return [];
        }

        $raw = $this->instagramService->getFeed($model);
        $dtos = [];

        foreach ($raw as $item) {
            if (empty($item['id'])) {
                continue;
            }

            $mediaType = $item['media_type'] ?? null;
            $mediaUrl = $item['media_url'] ?? null;
            $thumbnailUrl = $item['thumbnail_url'] ?? null;
            if ($mediaType === FeedPost::MEDIA_TYPE_IMAGE && $thumbnailUrl === null) {
                $thumbnailUrl = $mediaUrl;
            }

            $dtos[] = new FeedPostDto(
                externalId: (string) $item['id'],
                permalink: $item['permalink'] ?? null,
                caption: $item['caption'] ?? null,
                mediaType: $mediaType,
                mediaUrl: $mediaUrl,
                thumbnailUrl: $thumbnailUrl,
                duration: null,
                publishedAt: isset($item['timestamp']) ? new DateTimeImmutable($item['timestamp']) : null,
                payload: $item,
            );
        }

        return $dtos;
    }
}
