<?php

declare(strict_types=1);

namespace App\Message\Analytics;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * TrackEventMessage.
 *
 * Carries an anonymous, server-resolved analytics event to the async queue.
 * No personal data: IP and User-Agent are never serialized;
 * only their hash and the parsed dimensions are.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessage('async')]
final readonly class TrackEventMessage
{
    /**
     * @param array<string, scalar|null>|null $payload
     */
    public function __construct(
        public int $websiteId,
        public string $sessionHash,
        public string $eventType,
        public string $urlPath,
        public string $occurredAt,
        public ?string $referrerDomain = null,
        public ?string $countryCode = null,
        public ?string $device = null,
        public ?string $browser = null,
        public ?string $os = null,
        public ?string $locale = null,
        public ?string $viewport = null,
        public ?array $payload = null,
    ) {
    }
}
