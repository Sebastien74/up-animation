<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CacheDebugSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onResponse', -2048]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $started = $request->hasSession() && $request->getSession()->isStarted();
        $keys = $started ? implode('|', array_keys($request->getSession()->all())) : '';

        $response->headers->set('X-Debug-Session', $started ? 'started:'.$keys : 'none');
        $response->headers->set('X-Debug-Cookie-In', $request->cookies->has('PHPSESSID') ? '1' : '0');
    }
}
