<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Model\Api\InstagramModel;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * InstagramService.
 *
 * Service for fetching Instagram feed.
 */
class InstagramService
{
    private const string API_URL = 'https://graph.instagram.com/me/media';
    private const string REFRESH_TOKEN_URL = 'https://graph.instagram.com/refresh_access_token';
    private const string AUTH_URL = 'https://api.instagram.com/oauth/authorize';
    private const string TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
    private const string LONG_LIVED_TOKEN_URL = 'https://graph.instagram.com/access_token';
    private const int CACHE_EXPIRE = 3600; // 1 hour

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Get Instagram feed.
     *
     * @throws InvalidArgumentException
     */
    public function getFeed(InstagramModel $instagramModel): array
    {
        $accessToken = $instagramModel->accessToken;
        if (!$accessToken) {
            return [];
        }

        $cacheKey = 'instagram_feed_' . md5($accessToken);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($accessToken, $instagramModel) {
            $item->expiresAfter(self::CACHE_EXPIRE);

            try {
                $response = $this->httpClient->request('GET', self::API_URL, [
                    'query' => [
                        'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
                        'access_token' => $accessToken,
                        'limit' => $instagramModel->nbrItems ?: 10,
                    ],
                ]);
                if ($response->getStatusCode() !== 200) {
                    return [];
                }
                $data = $response->toArray();
                return $data['data'] ?? [];
            } catch (Throwable $e) {
                // Log error if needed
                return [];
            }
        });
    }

    /**
     * Refresh access token.
     * Note: Long-lived tokens are valid for 60 days and can be refreshed after 24 hours.
     */
    public function refreshToken(string $accessToken): ?string
    {
        try {
            $response = $this->httpClient->request('GET', self::REFRESH_TOKEN_URL, [
                'query' => [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $accessToken,
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

    /**
     * Get authorization URL.
     */
    public function getAuthUrl(string $appId): string
    {
        $redirectUri = $this->urlGenerator->generate('instagram_auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => 'user_profile,user_media',
            'response_type' => 'code',
        ]);
    }

    /**
     * Exchange code for a long-lived access token.
     */
    public function getLongLivedToken(string $appId, string $appSecret, string $code): ?string
    {
        $redirectUri = $this->urlGenerator->generate('instagram_auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            // 1. Get a short-lived token
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ],
            ]);


            if ($response->getStatusCode() !== 200) {
                // Return original response content for debugging if needed (via toArray(false))
                $errorData = $response->toArray(false);
                // In a production environment, you should use a LoggerInterface to log $errorData
                // For example: $this->logger->error('Instagram API Auth Error: ', $errorData);
                return null;
            }

            $data = $response->toArray();
            $shortLivedToken = $data['access_token'] ?? null;

            if (!$shortLivedToken) {
                return null;
            }

            // 2. Exchange for long-lived token
            $response = $this->httpClient->request('GET', self::LONG_LIVED_TOKEN_URL, [
                'query' => [
                    'grant_type' => 'ig_exchange_token',
                    'client_secret' => $appSecret,
                    'access_token' => $shortLivedToken,
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
