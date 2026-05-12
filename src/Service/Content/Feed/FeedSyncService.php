<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Entity\Api\FeedPost;
use App\Repository\Api\FeedPostRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * FeedSyncService.
 *
 * Orchestrates the synchronization of social feeds (Instagram, TikTok, ...)
 * into the local DB and downloads their medias under /public/feed/medias.
 * Posts present in DB but absent from the latest API response are
 * soft-deleted (removedAt set to now).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class FeedSyncService
{
    /**
     * @param iterable<FeedFetcherInterface> $fetchers
     */
    public function __construct(
        #[AutowireIterator('app.feed_fetcher')] private iterable $fetchers,
        private FeedPostRepository $feedPostRepository,
        private FeedMediaDownloader $mediaDownloader,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Sync one or all providers.
     *
     * @return array<string, array{added: int, updated: int, removed: int, mediaDownloaded: int}>
     */
    public function sync(?string $providerName = null, bool $force = false): array
    {
        $results = [];
        $matched = false;

        foreach ($this->fetchers as $fetcher) {
            if ($providerName !== null && $fetcher->provider() !== $providerName) {
                continue;
            }
            $matched = true;
            $results[$fetcher->provider()] = $this->syncProvider($fetcher, $force);
        }

        if ($providerName !== null && !$matched) {
            throw new InvalidArgumentException(sprintf('Unknown feed provider "%s".', $providerName));
        }

        return $results;
    }

    /**
     * @return array{added: int, updated: int, removed: int, mediaDownloaded: int}
     */
    private function syncProvider(FeedFetcherInterface $fetcher, bool $force): array
    {
        $provider = $fetcher->provider();
        $stats = ['added' => 0, 'updated' => 0, 'removed' => 0, 'mediaDownloaded' => 0];

        $dtos = $fetcher->fetch();
        if ($dtos === []) {
            return $stats;
        }

        $now = new DateTimeImmutable();
        $seen = [];

        foreach ($dtos as $dto) {
            $seen[] = $dto->externalId;
            $post = $this->feedPostRepository->findOneByExternal($provider, $dto->externalId);
            $isNew = $post === null;

            if ($isNew) {
                $post = new FeedPost();
                $post->setProvider($provider);
                $post->setExternalId($dto->externalId);
                $stats['added']++;
            } else {
                $stats['updated']++;
            }

            $post->setPermalink($dto->permalink);
            $post->setCaption($dto->caption);
            $post->setMediaType($dto->mediaType);
            $post->setDuration($dto->duration);
            $post->setPublishedAt($dto->publishedAt);
            $post->setRemovedAt(null);
            $post->setSyncedAt($now);
            $post->setPayload($dto->payload);

            if ($dto->mediaUrl) {
                $path = $this->mediaDownloader->download(
                    $dto->mediaUrl,
                    $provider,
                    $dto->externalId,
                    FeedMediaDownloader::KIND_MEDIA,
                    $force
                );
                if ($path) {
                    $post->setMediaLocalPath($path);
                    $stats['mediaDownloaded']++;
                }
            }

            if ($dto->thumbnailUrl) {
                $path = $this->mediaDownloader->download(
                    $dto->thumbnailUrl,
                    $provider,
                    $dto->externalId,
                    FeedMediaDownloader::KIND_THUMBNAIL,
                    $force
                );
                if ($path) {
                    $post->setThumbnailLocalPath($path);
                    $stats['mediaDownloaded']++;
                }
            }

            if ($isNew) {
                $this->entityManager->persist($post);
            }
        }

        $activeExternalIds = $this->feedPostRepository->findActiveExternalIds($provider);
        foreach (array_diff($activeExternalIds, $seen) as $missingExternalId) {
            $post = $this->feedPostRepository->findOneByExternal($provider, $missingExternalId);
            if ($post && $post->getRemovedAt() === null) {
                $post->setRemovedAt($now);
                $stats['removed']++;
            }
        }

        $this->entityManager->flush();

        return $stats;
    }
}
