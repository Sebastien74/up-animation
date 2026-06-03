<?php

declare(strict_types=1);

namespace App\Controller\Front\Analytics;

use App\Entity\Analytics\AnalyticsEvent;
use App\Message\Analytics\TrackEventMessage;
use App\Service\Analytics\BotDetector;
use App\Service\Analytics\GeoIpResolverInterface;
use App\Service\Analytics\SaltRotator;
use App\Service\Analytics\UserAgentParser;
use App\Service\Analytics\WebsiteResolver;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * IngestController.
 *
 * Public, anonymous ingestion endpoint for the analytics tracker.
 * Performs all PII-touching work synchronously (hash, UA parse)
 * and dispatches a clean message to the async queue.
 *
 * Always returns 204 No Content, even on invalid payload or bot
 * traffic, to avoid leaking validation details or detection logic.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class IngestController extends AbstractController
{
    private const array ALLOWED_TYPES = [
        AnalyticsEvent::TYPE_PAGEVIEW,
        AnalyticsEvent::TYPE_CLICK,
        AnalyticsEvent::TYPE_SCROLL,
        AnalyticsEvent::TYPE_FORM,
    ];
    private const int MAX_URL_LENGTH = 512;
    private const int MAX_REFERRER_LENGTH = 2048;
    private const int MAX_PAYLOAD_KEYS = 16;
    private const int MAX_PAYLOAD_VALUE_LENGTH = 256;

    public function __construct(
        private readonly SaltRotator $saltRotator,
        private readonly WebsiteResolver $websiteResolver,
        private readonly BotDetector $botDetector,
        private readonly UserAgentParser $userAgentParser,
        private readonly GeoIpResolverInterface $geoIpResolver,
        private readonly MessageBusInterface $messageBus,
        private readonly CoreLocatorInterface $coreLocator,
        #[Target('analytics_ingest.limiter')]
        private readonly RateLimiterFactoryInterface $ingestLimiter,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(
        path: '/a/c',
        name: 'front_analytics_ingest',
        options: ['isMainRequest' => false],
        methods: 'POST',
        schemes: '%protocol%',
        priority: 1000
    )]
    public function ingest(Request $request): Response
    {
        $ip = $request->getClientIp();
        $userAgent = (string) $request->headers->get('User-Agent', '');

        if (null === $ip || $this->botDetector->isBot($userAgent) || $this->coreLocator->checkIP()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        if (false === $this->ingestLimiter->create($ip)->consume()->isAccepted()) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $payload = $this->decodePayload($request);
        if (null === $payload) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $websiteId = $this->websiteResolver->resolve($request->getHost());
        if (null === $websiteId) {
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $dimensions = $this->userAgentParser->parse($userAgent);
        $sessionHash = $this->saltRotator->hashSession($ip, $userAgent);
        $countryCode = $this->geoIpResolver->resolveCountry($ip);

        $this->messageBus->dispatch(new TrackEventMessage(
            websiteId: $websiteId,
            sessionHash: $sessionHash,
            eventType: $payload['type'],
            urlPath: $payload['url'],
            occurredAt: (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s.v'),
            referrerDomain: $payload['referrer'],
            countryCode: $countryCode,
            device: $dimensions['device'],
            browser: $dimensions['browser'],
            os: $dimensions['os'],
            locale: $payload['locale'],
            viewport: $payload['viewport'],
            payload: $payload['payload'],
        ));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{
     *     type: string,
     *     url: string,
     *     referrer: ?string,
     *     locale: ?string,
     *     viewport: ?string,
     *     payload: ?array<string, scalar|null>
     * }|null
     */
    private function decodePayload(Request $request): ?array
    {
        try {
            $data = json_decode($request->getContent(), true, 4, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($data)) {
            return null;
        }

        $type = is_string($data['type'] ?? null) ? $data['type'] : null;
        $url = is_string($data['url'] ?? null) ? $data['url'] : null;

        if (null === $type
            || null === $url
            || !in_array($type, self::ALLOWED_TYPES, true)
            || '' === $url
            || strlen($url) > self::MAX_URL_LENGTH
            || !str_starts_with($url, '/')
        ) {
            return null;
        }

        return [
            'type' => $type,
            'url' => $url,
            'referrer' => $this->sanitizeReferrer($data['referrer'] ?? null),
            'locale' => $this->sanitizeShortString($data['locale'] ?? null, 8),
            'viewport' => $this->sanitizeShortString($data['viewport'] ?? null, 16),
            'payload' => $this->sanitizePayload($data['payload'] ?? null),
        ];
    }

    private function sanitizeReferrer(mixed $referrer): ?string
    {
        if (!is_string($referrer) || '' === $referrer || strlen($referrer) > self::MAX_REFERRER_LENGTH) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) && '' !== $host ? strtolower($host) : null;
    }

    private function sanitizeShortString(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value) || '' === $value || strlen($value) > $maxLength) {
            return null;
        }

        return $value;
    }

    /**
     * @return array<string, scalar|null>|null
     */
    private function sanitizePayload(mixed $payload): ?array
    {
        if (!is_array($payload) || [] === $payload) {
            return null;
        }

        $clean = [];
        $count = 0;
        foreach ($payload as $key => $value) {
            if (++$count > self::MAX_PAYLOAD_KEYS) {
                break;
            }
            if (!is_string($key) || (!is_scalar($value) && null !== $value)) {
                continue;
            }
            if (is_string($value) && strlen($value) > self::MAX_PAYLOAD_VALUE_LENGTH) {
                continue;
            }
            $clean[$key] = $value;
        }

        return [] === $clean ? null : $clean;
    }
}
