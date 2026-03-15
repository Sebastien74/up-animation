<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\Api\FacebookModel;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * FacebookService.
 *
 * Service for fetching Facebook Page feed.
 */
class FacebookService
{
    private const string API_URL = 'https://graph.facebook.com/%s/feed';
    private const int CACHE_EXPIRE = 3600; // 1 hour

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get a Facebook feed.
     *
     * @throws InvalidArgumentException
     */
    public function getFeed(FacebookModel $facebookModel): array
    {
        $accessToken = $facebookModel->accessToken;
        $pageId = $facebookModel->pageId;

        if (!$accessToken || !$pageId) {
            return [];
        }

        $cacheKey = 'facebook_feed_' . md5($accessToken . $pageId);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($accessToken, $pageId, $facebookModel) {
            $item->expiresAfter(self::CACHE_EXPIRE);

            try {
                $url = sprintf(self::API_URL, $pageId);
                $response = $this->httpClient->request('GET', $url, [
                    'query' => [
                        'fields' => 'id,message,created_time,full_picture,permalink_url,attachments{media,type,url,subattachments}',
                        'access_token' => $accessToken,
                        'limit' => $facebookModel->nbrItems ?: 10,
                    ],
                ]);
                if ($response->getStatusCode() !== 200) {
                    return [];
                }
                $data = $response->toArray();
                return $data['data'] ?? [];
            } catch (Throwable) {
                return [];
            }
        });
    }
}
