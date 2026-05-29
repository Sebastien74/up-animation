<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Model\Api\GoogleModel;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * GoogleReviewService.
 *
 * Service for fetching Google Business reviews via Places API.
 */
class GoogleReviewService
{
    private const string API_URL = 'https://maps.googleapis.com/maps/api/place/details/json';
    private const int CACHE_EXPIRE = 86400; // 24 hours (reviews don't change that often)

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get Google reviews.
     *
     * @throws InvalidArgumentException
     */
    public function getReviews(GoogleModel $googleModel): array
    {
        $apiKey = $googleModel->mapKey;
        $placeId = $googleModel->placeId;

        if (!$apiKey || !$placeId) {
            return [];
        }

        $cacheKey = 'google_reviews_' . md5($apiKey . $placeId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($apiKey, $placeId, $googleModel) {
            $item->expiresAfter(self::CACHE_EXPIRE);
            try {
                $response = $this->httpClient->request('GET', self::API_URL, [
                    'query' => [
                        'place_id' => $placeId,
                        'fields' => 'reviews,rating,user_ratings_total',
                        'key' => $apiKey,
                        'reviews_sort' => 'newest',
                        'language' => 'fr', // Default to French
                    ],
                ]);

                if ($response->getStatusCode() !== 200) {
                    return [];
                }

                $data = $response->toArray();
                $result = $data['result'] ?? [];
                $reviews = $result['reviews'] ?? [];

                // Limit results if needed (API returns up to 5 reviews)
                if ($googleModel->googleReviewsNbrItems && count($reviews) > $googleModel->googleReviewsNbrItems) {
                    $reviews = array_slice($reviews, 0, $googleModel->googleReviewsNbrItems);
                }

                return [
                    'reviews' => $reviews,
                    'rating' => $result['rating'] ?? null,
                    'user_ratings_total' => $result['user_ratings_total'] ?? null,
                ];
            } catch (Throwable) {
                return [];
            }
        });
    }
}
