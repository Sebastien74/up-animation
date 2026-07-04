<?php

declare(strict_types=1);

namespace App\Service\Security;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

/**
 * CaptchaService.
 *
 * Self-hosted, privacy-friendly anti-bot challenge (no third party, no tracking).
 * ALTCHA-compatible proof-of-work hardened with a stateless HMAC signature, a
 * time-trap and a single-use replay guard. Pure and clock/cache injected so the
 * whole verification path is unit-testable.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class CaptchaService
{
    public const string ALGORITHM = 'SHA-256';

    public function __construct(
        private readonly CacheItemPoolInterface $captchaReplayPool,
        private readonly ClockInterface $clock,
        private readonly int $maxNumber = 120_000,
        private readonly int $expirySeconds = 600,
        private readonly int $minSolveSeconds = 3,
    ) {
    }

    /**
     * Issue a fresh proof-of-work challenge bound to the website secret.
     *
     * @return array<string, int|string>
     */
    public function issue(string $hmacKey): array
    {
        $now = $this->clock->now()->getTimestamp();
        $salt = bin2hex(random_bytes(12)).'?expires='.($now + $this->expirySeconds).'&ts='.$now;
        $secretNumber = random_int(0, $this->maxNumber);
        $challenge = hash('sha256', $salt.$secretNumber);

        return [
            'algorithm' => self::ALGORITHM,
            'challenge' => $challenge,
            'salt' => $salt,
            'signature' => $this->sign($challenge, $hmacKey),
            'maxnumber' => $this->maxNumber,
        ];
    }

    /**
     * Verify a submitted solution. Fails closed on any anomaly.
     *
     * @param string|null $payload   base64(JSON) produced by the client solver
     * @param string|null $honeypot  the honeypot field value (must stay empty)
     */
    public function verify(string $hmacKey, ?string $payload, ?string $honeypot = null): bool
    {
        if (null !== $honeypot && '' !== trim($honeypot)) {
            return false;
        }

        $solution = $this->decode($payload);
        if (null === $solution) {
            return false;
        }

        $challenge = $solution['challenge'];

        if (!hash_equals($this->sign($challenge, $hmacKey), $solution['signature'])) {
            return false;
        }

        if (!hash_equals($challenge, hash('sha256', $solution['salt'].$solution['number']))) {
            return false;
        }

        $now = $this->clock->now()->getTimestamp();
        $params = $this->saltParams($solution['salt']);
        $issuedAt = $params['ts'] ?? null;
        $expires = $params['expires'] ?? null;

        if (null === $issuedAt || null === $expires) {
            return false;
        }
        if ($now > $expires || $now - $issuedAt < $this->minSolveSeconds) {
            return false;
        }

        return $this->consume($challenge, $expires - $now);
    }

    private function sign(string $challenge, string $hmacKey): string
    {
        return hash_hmac('sha256', $challenge, hash('sha256', $hmacKey, true));
    }

    /**
     * @return array{algorithm: string, challenge: string, number: int, salt: string, signature: string}|null
     */
    private function decode(?string $payload): ?array
    {
        if (null === $payload || '' === $payload) {
            return null;
        }

        $raw = base64_decode($payload, true);
        if (false === $raw) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)
            || (($data['algorithm'] ?? null) !== self::ALGORITHM)
            || !is_string($data['challenge'] ?? null)
            || !is_string($data['salt'] ?? null)
            || !is_string($data['signature'] ?? null)
            || !is_int($data['number'] ?? null)) {
            return null;
        }

        return [
            'algorithm' => $data['algorithm'],
            'challenge' => $data['challenge'],
            'number' => $data['number'],
            'salt' => $data['salt'],
            'signature' => $data['signature'],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function saltParams(string $salt): array
    {
        $query = strstr($salt, '?');
        if (false === $query) {
            return [];
        }

        parse_str(ltrim($query, '?'), $parsed);
        $params = [];
        foreach (['ts', 'expires'] as $key) {
            if (isset($parsed[$key]) && ctype_digit((string) $parsed[$key])) {
                $params[$key] = (int) $parsed[$key];
            }
        }

        return $params;
    }

    /**
     * Single-use guard: a given challenge can be redeemed only once.
     */
    private function consume(string $challenge, int $ttl): bool
    {
        $item = $this->captchaReplayPool->getItem('captcha_'.hash('sha256', $challenge));
        if ($item->isHit()) {
            return false;
        }

        $item->set(true)->expiresAfter(max(1, $ttl));
        $this->captchaReplayPool->save($item);

        return true;
    }
}
