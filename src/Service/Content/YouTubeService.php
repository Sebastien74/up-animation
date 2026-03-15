<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\Api\GoogleModel;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * YouTubeService.
 *
 * Service for fetching YouTube channel videos.
 */
class YouTubeService
{
    private const string API_URL = 'https://www.googleapis.com/youtube/v3/search';
    private const int CACHE_EXPIRE = 3600; // 1 hour

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get YouTube videos.
     *
     * @throws InvalidArgumentException
     */
    public function getVideos(GoogleModel $googleModel): array
    {
        $apiKey = $googleModel->youtubeApiKey;
        $channelId = $googleModel->youtubeChannelId;

        if (!$apiKey || !$channelId) {
            return [];
        }

        $cacheKey = 'youtube_feed_' . md5($apiKey . $channelId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($apiKey, $channelId, $googleModel) {
            $item->expiresAfter(self::CACHE_EXPIRE);
            try {
                $response = $this->httpClient->request('GET', self::API_URL, [
                    'query' => [
                        'key' => $apiKey,
                        'channelId' => $channelId,
                        'part' => 'snippet,id',
                        'order' => 'date',
                        'maxResults' => $googleModel->youtubeNbrItems ?: 10,
                        'type' => 'video',
                    ],
                ]);
                if ($response->getStatusCode() !== 200) {
                    return [];
                }
                $data = $response->toArray();
                return $data['items'] ?? [];
            } catch (Throwable) {
                return [];
            }
        });
    }
}
