<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * AnonymousCacheSubscriber.
 *
 * Makes strictly anonymous, cookieless front GET responses cacheable by the
 * shared cache (Varnish). Any response carrying a cookie (logged-in user,
 * form with session-backed CSRF) is left untouched and stays private.
 */
final class AnonymousCacheSubscriber implements EventSubscriberInterface
{
    private const int SHARED_MAX_AGE = 600;

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

        if ('GET' !== $request->getMethod() || 200 !== $response->getStatusCode()) {
            return;
        }

        if ($request->cookies->has('PHPSESSID') || $response->headers->has('Set-Cookie')) {
            return;
        }

        $path = $request->getPathInfo();
        if (str_contains($request->getUri(), '/admin-')
            || str_starts_with($path, '/_')
            || str_contains($path, '/secure')
            || str_contains($path, '/espace-personnel')
            || str_contains($path, '/personal-space')) {
            return;
        }

        $response->setPublic();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(self::SHARED_MAX_AGE);
        $response->headers->removeCacheControlDirective('no-cache');
        $response->headers->removeCacheControlDirective('no-store');
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');
    }
}
