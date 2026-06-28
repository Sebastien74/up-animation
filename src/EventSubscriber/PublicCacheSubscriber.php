<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * PublicCacheSubscriber.
 *
 * Lets the shared cache (Varnish) store strictly anonymous front responses.
 *
 * Safety: only GET/200 responses with NO incoming cookie at all and NO Set-Cookie
 * on the way out are touched. A logged-in user always carries a cookie (PHPSESSID,
 * REMEMBERME, SECURITY_*), so authenticated pages are never made public.
 *
 * PHP's session cache-limiter queues "no-store/no-cache" via raw header() and Symfony
 * *appends* headers rather than replacing them, so we clear them with header_remove()
 * before the response is sent, then set a clean public Cache-Control.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class PublicCacheSubscriber implements EventSubscriberInterface
{
    private const int SHARED_MAX_AGE = 600;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 4096],
            KernelEvents::RESPONSE => ['onResponse', -1024],
        ];
    }

    /**
     * Disable PHP's session cache-limiter before any session starts, so session_start()
     * never emits no-store/no-cache headers. Symfony controls Cache-Control itself.
     */
    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            @ini_set('session.cache_limiter', '');
            @session_cache_limiter('');
        }
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

        if ($request->cookies->count() > 0 || $response->headers->has('Set-Cookie')) {
            return;
        }

        if (str_contains($request->getUri(), '/admin-') || str_starts_with($request->getPathInfo(), '/_')) {
            return;
        }

        if (!headers_sent()) {
            header_remove('Cache-Control');
            header_remove('Expires');
            header_remove('Pragma');
        }

        // Drop any existing Cache-Control (raw string with no-store survives the directive
        // API) and rebuild a clean public one. Expires/Pragma removed too.
        $response->headers->remove('Expires');
        $response->headers->remove('Pragma');
        $response->headers->remove('Cache-Control');
        $response->headers->set('Cache-Control', 'public, s-maxage='.self::SHARED_MAX_AGE.', max-age=0');
    }
}
