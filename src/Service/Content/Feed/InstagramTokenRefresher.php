<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Repository\Api\InstagramRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * InstagramTokenRefresher.
 *
 * Keeps Instagram long-lived tokens alive. Meta tokens last 60 days and can be
 * refreshed (reset to 60 days) any time after they are 24 h old. Refreshing every
 * stored token that is close to expiry guarantees the feed sync never stops for
 * lack of a valid token.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class InstagramTokenRefresher
{
    // Refresh once the token is within this many days of its expiry.
    private const int REFRESH_THRESHOLD_DAYS = 10;

    public function __construct(
        private InstagramRepository $instagramRepository,
        private InstagramService $instagramService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Refresh every stored Instagram token that is due (or all of them when forced).
     *
     * @return array{refreshed: int, skipped: int, failed: int}
     */
    public function refresh(bool $force = false): array
    {
        $stats = ['refreshed' => 0, 'skipped' => 0, 'failed' => 0];
        $now = new DateTimeImmutable();
        $threshold = $now->modify('+'.self::REFRESH_THRESHOLD_DAYS.' days');

        foreach ($this->instagramRepository->findAll() as $instagram) {
            $token = $instagram->getAccessToken();
            if (!$token) {
                continue;
            }

            $expiresAt = $instagram->getTokenExpiresAt();
            if (!$force && $expiresAt !== null && $expiresAt > $threshold) {
                $stats['skipped']++;
                continue;
            }

            $result = $this->instagramService->refreshToken($token);
            if ($result === null) {
                // Token already expired or younger than 24 h: needs a manual OAuth re-connection.
                $stats['failed']++;
                continue;
            }

            $instagram->setAccessToken($result['access_token']);
            $instagram->setTokenExpiresAt(
                $result['expires_in'] > 0 ? $now->modify('+'.$result['expires_in'].' seconds') : null
            );
            $stats['refreshed']++;
        }

        if ($stats['refreshed'] > 0) {
            $this->entityManager->flush();
        }

        return $stats;
    }
}
