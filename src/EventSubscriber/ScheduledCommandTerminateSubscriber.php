<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Core\CronHeartbeatService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * ScheduledCommandTerminateSubscriber.
 *
 * Drives the scheduled-command engine from web traffic after the HTTP
 * response has been sent, removing the need for an external cron service
 * on shared hosting. The heartbeat is throttled to once per minute.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class ScheduledCommandTerminateSubscriber implements EventSubscriberInterface
{
    public function __construct(private CronHeartbeatService $heartbeat)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $this->heartbeat->runIfDue();
    }
}
