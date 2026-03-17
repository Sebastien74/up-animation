<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\Api\TikTokModel;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    private const string AUTH_URL = 'https://www.tiktok.com/v2/auth/authorize/';
    private const string TOKEN_URL = 'https://open.tiktokapis.com/v2/oauth/token/';
    private const int CACHE_EXPIRE = 3600; // 1 hour

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator
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

    /**
     * Get authorization URL.
     */
    public function getAuthUrl(string $clientKey): string
    {
        $redirectUri = $this->urlGenerator->generate('tiktok_auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return self::AUTH_URL . '?' . http_build_query([
            'client_key' => $clientKey,
            'redirect_uri' => $redirectUri,
            'scope' => 'user.info.basic,video.list',
            'response_type' => 'code',
        ]);
    }

    /**
     * Exchange code for an access token.
     */
    public function getAccessToken(string $clientKey, string $clientSecret, string $code): ?string
    {
        $redirectUri = $this->urlGenerator->generate('tiktok_auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'client_key' => $clientKey,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['access_token'] ?? null;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
