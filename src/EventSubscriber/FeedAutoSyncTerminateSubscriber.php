<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Content\Feed\FeedAutoSyncService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * FeedAutoSyncTerminateSubscriber.
 *
 * Triggers any feed syncs queued during the request after the HTTP
 * response has been sent to the browser, so the user sees no slowdown.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class FeedAutoSyncTerminateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FeedAutoSyncService $autoSyncService,
        private bool $webCronEnabled = true,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->webCronEnabled) {
            return;
        }
        $this->autoSyncService->runScheduled();
    }
}
