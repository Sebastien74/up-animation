<?php

declare(strict_types=1);

namespace App\MessageHandler\Analytics;

use App\Entity\Analytics\AnalyticsEvent;
use App\Message\Analytics\TrackEventMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * TrackEventHandler.
 *
 * Pure persister: converts an anonymous TrackEventMessage into an
 * AnalyticsEvent row. All PII work (hash, geo, UA parse) has already
 * been done synchronously in the ingestion controller.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessageHandler]
final readonly class TrackEventHandler
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(TrackEventMessage $message): void
    {
        try {
            $occurredAt = new \DateTimeImmutable($message->occurredAt, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return;
        }

        $event = (new AnalyticsEvent())
            ->setWebsiteId($message->websiteId)
            ->setSessionHash($message->sessionHash)
            ->setEventType($message->eventType)
            ->setUrlPath($message->urlPath)
            ->setOccurredAt($occurredAt)
            ->setReferrerDomain($message->referrerDomain)
            ->setCountryCode($message->countryCode)
            ->setDevice($message->device)
            ->setBrowser($message->browser)
            ->setOs($message->os)
            ->setLocale($message->locale)
            ->setViewport($message->viewport)
            ->setEventPayload($message->payload);

        $this->entityManager->persist($event);
        $this->entityManager->flush();
        $this->entityManager->detach($event);
    }
}
