<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Model\Api\TikTokModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get TikTok feed (raw API response).
     *
     * No caching here: callers (FeedSyncService via TikTokFeedFetcher)
     * are throttled by the app:feed:sync cron cadence (external cron, no traffic-driven sync).
     */
    public function getFeed(TikTokModel $tiktokModel): array
    {
        $accessToken = $tiktokModel->accessToken;
        if (!$accessToken) {
            return [];
        }

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
     * Exchange an authorization code for a token set.
     *
     * @return array{access_token: string, refresh_token: string|null, expires_in: int, refresh_expires_in: int}|null
     */
    public function getAccessToken(string $clientKey, string $clientSecret, string $code): ?array
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

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $result = $this->parseTokenResponse($response->toArray());
                if ($result === null) {
                    // Never log the code/secret/tokens, only the error envelope returned by TikTok.
                    $this->logger->error('TikTok token exchange: 200 response without access_token.', [
                        'response' => $this->redactTokenResponse($response->getContent(false)),
                    ]);
                }

                return $result;
            }

            $this->logger->error('TikTok token exchange failed.', [
                'status' => $statusCode,
                'response' => $this->redactTokenResponse($response->getContent(false)),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('TikTok token exchange error: '.$exception->getMessage());
        }

        return null;
    }

    /**
     * Exchange the refresh token for a fresh token set.
     *
     * TikTok rotates the refresh_token on every call: the response carries a new
     * refresh_token that must replace the previous one.
     *
     * @return array{access_token: string, refresh_token: string|null, expires_in: int, refresh_expires_in: int}|null
     */
    public function refreshToken(string $clientKey, string $clientSecret, string $refreshToken): ?array
    {
        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'client_key' => $clientKey,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $result = $this->parseTokenResponse($response->toArray());
                if ($result === null) {
                    // Never log the tokens, only the error envelope returned by TikTok.
                    $this->logger->error('TikTok token refresh: 200 response without access_token.', [
                        'response' => $this->redactTokenResponse($response->getContent(false)),
                    ]);
                }

                return $result;
            }

            $this->logger->error('TikTok token refresh failed.', [
                'status' => $statusCode,
                'response' => $this->redactTokenResponse($response->getContent(false)),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('TikTok token refresh error: '.$exception->getMessage());
        }

        return null;
    }

    /**
     * Normalize a TikTok oauth/token response into a token set.
     *
     * @param array<string, mixed> $data
     *
     * @return array{access_token: string, refresh_token: string|null, expires_in: int, refresh_expires_in: int}|null
     */
    private function parseTokenResponse(array $data): ?array
    {
        $token = $data['access_token'] ?? null;
        if (!is_string($token) || $token === '') {
            return null;
        }

        $refreshToken = $data['refresh_token'] ?? null;

        return [
            'access_token' => $token,
            'refresh_token' => is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : null,
            'expires_in' => (int) ($data['expires_in'] ?? 0),
            'refresh_expires_in' => (int) ($data['refresh_expires_in'] ?? 0),
        ];
    }

    /**
     * Strip secret fields from a token response body so it is safe to log.
     *
     * @return array<string, mixed> Redacted payload, or a raw placeholder when the body is not JSON
     */
    private function redactTokenResponse(string $body): array
    {
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return ['raw' => '[unparseable response body]'];
        }

        foreach (['access_token', 'refresh_token', 'open_id'] as $key) {
            unset($data[$key]);
            if (isset($data['data']) && is_array($data['data'])) {
                unset($data['data'][$key]);
            }
        }

        return $data;
    }
}
