<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\Api\TikTokModel;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * TikTokService.
 *
 * Service for fetching TikTok feed using TikTok Display API.
 */
class TikTokService
{
    private const string API_URL = 'https://open.tiktokapis.com/v2/video/list/';
    private const int CACHE_EXPIRE = 3600; // 1 hour

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get TikTok feed.
     *
     * @throws InvalidArgumentException
     */
    public function getFeed(TikTokModel $tiktokModel): array
    {
        $accessToken = $tiktokModel->accessToken;

        if (!$accessToken) {
            return [];
        }

        $cacheKey = 'tiktok_feed_' . md5($accessToken);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($accessToken, $tiktokModel) {
            $item->expiresAfter(self::CACHE_EXPIRE);

            try {
                $response = $this->httpClient->request('POST', self::API_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'fields' => 'id,create_time,cover_image_url,share_url,video_description,duration,title',
                        'max_count' => $tiktokModel->nbrItems ?: 10,
                    ],
                ]);

                if ($response->getStatusCode() !== 200) {
                    return [];
                }

                $data = $response->toArray();
                return $data['data']['videos'] ?? [];
            } catch (Throwable) {
                return [];
            }
        });
    }
}
