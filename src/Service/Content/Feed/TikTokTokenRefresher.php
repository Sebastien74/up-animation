<?php

declare(strict_types=1);

namespace App\Service\Content\Feed;

use App\Repository\Api\TikTokRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * TikTokTokenRefresher.
 *
 * Keeps TikTok tokens alive. Access tokens last 24 h, refresh tokens 365 days and
 * rotate on every refresh. Refreshing each access token before it lapses (and
 * persisting the rotated refresh token) keeps the feed sync from breaking.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class TikTokTokenRefresher
{
    // Refresh once the 24 h access token is within this many hours of its expiry.
    private const int REFRESH_THRESHOLD_HOURS = 12;

    public function __construct(
        private TikTokRepository $tiktokRepository,
        private TikTokService $tiktokService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Refresh every stored TikTok token that is due (or all of them when forced).
     *
     * @return array{refreshed: int, skipped: int, failed: int}
     */
    public function refresh(bool $force = false): array
    {
        $stats = ['refreshed' => 0, 'skipped' => 0, 'failed' => 0];
        $now = new DateTimeImmutable();
        $threshold = $now->modify('+'.self::REFRESH_THRESHOLD_HOURS.' hours');
        $changed = false;

        foreach ($this->tiktokRepository->findAll() as $tiktok) {
            if (!$tiktok->getAccessToken()) {
                continue;
            }

            $refreshToken = $tiktok->getRefreshToken();
            if (!$refreshToken) {
                // Token connected before refresh support, or refresh token lost: needs a manual OAuth re-connection.
                $stats['failed']++;
                continue;
            }

            $expiresAt = $tiktok->getTokenExpiresAt();
            if (!$force && $expiresAt !== null && $expiresAt > $threshold) {
                $stats['skipped']++;
                continue;
            }

            $result = $this->tiktokService->refreshToken(
                (string) $tiktok->getAppId(),
                (string) $tiktok->getAppSecret(),
                $refreshToken
            );
            if ($result === null) {
                // Refresh token expired (365 days untouched) or app credentials changed: manual OAuth required.
                $stats['failed']++;
                continue;
            }

            $tiktok->setAccessToken($result['access_token']);
            if ($result['refresh_token'] !== null) {
                $tiktok->setRefreshToken($result['refresh_token']);
            }
            $tiktok->setTokenExpiresAt(
                $result['expires_in'] > 0 ? $now->modify('+'.$result['expires_in'].' seconds') : null
            );
            if ($result['refresh_expires_in'] > 0) {
                $tiktok->setRefreshTokenExpiresAt($now->modify('+'.$result['refresh_expires_in'].' seconds'));
            }
            $stats['refreshed']++;
            $changed = true;
        }

        if ($changed) {
            $this->entityManager->flush();
        }

        return $stats;
    }
}
