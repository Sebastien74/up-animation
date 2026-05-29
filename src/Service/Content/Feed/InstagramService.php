<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Model\Api\InstagramModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    private const string AUTH_URL = 'https://www.instagram.com/oauth/authorize';
    private const string TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
    private const string LONG_LIVED_TOKEN_URL = 'https://graph.instagram.com/access_token';
    private const string SCOPE = 'instagram_business_basic';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get Instagram feed (raw API response).
     *
     * No caching here: callers (FeedSyncService via InstagramFeedFetcher)
     * already throttle invocations via FeedAutoSyncService's 12 h lock.
     */
    public function getFeed(InstagramModel $instagramModel): array
    {
        $accessToken = $instagramModel->accessToken;
        if (!$accessToken) {
            return [];
        }

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
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Refresh a long-lived access token.
     *
     * Long-lived tokens are valid for 60 days and can only be refreshed once they
     * are at least 24 h old (Meta rejects a refresh on a token younger than that).
     *
     * @return array{access_token: string, expires_in: int}|null Fresh token and its lifetime in seconds, null on failure
     */
    public function refreshToken(string $accessToken): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::REFRESH_TOKEN_URL, [
                'query' => [
                    'grant_type' => 'ig_refresh_token',
                    'access_token' => $accessToken,
                ],
            ]);
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = $response->toArray();
                $token = $data['access_token'] ?? null;
                if (!is_string($token) || $token === '') {
                    // Never log the token itself, only the error envelope returned by Meta.
                    $this->logger->error('Instagram token refresh: 200 response without access_token.', [
                        'response' => $response->getContent(false),
                    ]);

                    return null;
                }

                return [
                    'access_token' => $token,
                    'expires_in' => (int) ($data['expires_in'] ?? 0),
                ];
            }

            $this->logger->error('Instagram token refresh failed.', [
                'status' => $statusCode,
                'response' => $response->getContent(false),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Instagram token refresh error: '.$exception->getMessage());
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
            'enable_fb_login' => '0',
            'force_authentication' => '1',
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
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

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                // Never log the code/secret, only the error envelope returned by Meta.
                $this->logger->error('Instagram short-lived token exchange failed.', [
                    'status' => $statusCode,
                    'response' => $response->getContent(false),
                ]);

                return null;
            }

            $data = $response->toArray();
            $shortLivedToken = $data['access_token'] ?? null;

            if (!$shortLivedToken) {
                $this->logger->error('Instagram short-lived token exchange: 200 response without access_token.', [
                    'response' => $response->getContent(false),
                ]);

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

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $data = $response->toArray();
                $token = $data['access_token'] ?? null;
                if (!is_string($token) || $token === '') {
                    $this->logger->error('Instagram long-lived token exchange: 200 response without access_token.', [
                        'response' => $response->getContent(false),
                    ]);

                    return null;
                }

                return $token;
            }

            $this->logger->error('Instagram long-lived token exchange failed.', [
                'status' => $statusCode,
                'response' => $response->getContent(false),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Instagram token exchange error: '.$exception->getMessage());
        }

        return null;
    }
}
