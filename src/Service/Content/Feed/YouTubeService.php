<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Model\Api\GoogleModel;
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

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * Get YouTube channel videos (raw API response).
     *
     * No caching here: callers (FeedSyncService via YouTubeFeedFetcher)
     * are throttled by the app:feed:sync cron cadence (external cron, no traffic-driven sync).
     */
    public function getVideos(GoogleModel $googleModel): array
    {
        $apiKey = $googleModel->youtubeApiKey;
        $channelId = $googleModel->youtubeChannelId;

        if (!$apiKey || !$channelId) {
            return [];
        }

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
    }
}
