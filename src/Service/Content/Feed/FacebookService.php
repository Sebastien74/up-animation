<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Model\Api\FacebookModel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    private const string AUTH_URL = 'https://www.facebook.com/v19.0/dialog/oauth';
    private const string TOKEN_URL = 'https://graph.facebook.com/v19.0/oauth/access_token';
    private const string PAGE_TOKEN_URL = 'https://graph.facebook.com/v19.0/%s';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Get a Facebook Page feed (raw API response).
     *
     * No caching here: callers (FeedSyncService via FacebookFeedFetcher)
     * already throttle invocations via FeedAutoSyncService's 12 h lock.
     */
    public function getFeed(FacebookModel $facebookModel): array
    {
        $accessToken = $facebookModel->accessToken;
        $pageId = $facebookModel->pageId;

        if (!$accessToken || !$pageId) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', sprintf(self::API_URL, $pageId), [
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
    }

    /**
     * Get authorization URL.
     */
    public function getAuthUrl(string $appId): string
    {
        $redirectUri = $this->urlGenerator->generate('facebook_auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => 'pages_read_engagement,pages_show_list',
            'response_type' => 'code',
        ]);
    }

    /**
     * Exchange code for a page access token.
     */
    public function getPageAccessToken(string $appId, string $appSecret, string $pageId, string $code): ?string
    {
        $redirectUri = $this->urlGenerator->generate('facebook_auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            // 1. Get User Access Token
            $response = $this->httpClient->request('GET', self::TOKEN_URL, [
                'query' => [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();
            $userToken = $data['access_token'] ?? null;

            if (!$userToken) {
                return null;
            }

            // 2. Get Page Access Token
            $url = sprintf(self::PAGE_TOKEN_URL, $pageId);
            $response = $this->httpClient->request('GET', $url, [
                'query' => [
                    'fields' => 'access_token',
                    'access_token' => $userToken,
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
