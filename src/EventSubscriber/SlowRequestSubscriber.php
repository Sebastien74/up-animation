<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Core\SlowRequestStatsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * SlowRequestSubscriber.
 *
 * Captures request duration and logs every main request slower than a configurable threshold.
 * Used as a profiling probe to identify which URLs cause occasional display latency.
 *
 * Measurement window: starts on kernel.request (priority 4096) and stops on kernel.response
 * at a priority higher than the Symfony profiler / web debug toolbar listeners. This way
 * the recorded duration approximates a production execution and does not include the
 * profiler data collection overhead. The actual log write happens on kernel.terminate
 * after the response has been sent, so logging never impacts the user-perceived latency.
 *
 * GDPR notice: this probe deliberately stores no personal data. IP addresses are never
 * recorded, and only the request path (no query string) is kept so URLs cannot accidentally
 * leak personal data carried by query parameters.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class SlowRequestSubscriber implements EventSubscriberInterface
{
    private const string REQUEST_START_ATTR = '_slow_request_start';
    private const string REQUEST_DURATION_ATTR = '_slow_request_duration_ms';

    /** Dev/debug routes that exist only in non-prod envs and would pollute the metrics. */
    private const array EXCLUDED_ROUTES = [
        '_wdt' => true,
        '_profiler' => true,
        '_profiler_home' => true,
        '_profiler_search' => true,
        '_profiler_search_bar' => true,
        '_profiler_search_results' => true,
        '_profiler_router' => true,
        '_profiler_exception' => true,
        '_profiler_exception_css' => true,
        '_fragment' => true,
        'front_activity' => true,
    ];

    public function __construct(
        private readonly LoggerInterface $slowRequestLogger,
        private readonly SlowRequestStatsService $statsService,
        private readonly int $thresholdMs,
        private readonly bool $enabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Captured as early as possible to include framework boot inside the measurement.
            KernelEvents::REQUEST => ['onRequest', 4096],
            // Stops the timer BEFORE the Symfony profiler/web-debug-toolbar listeners run
            // (those use negative priorities). Result: prod-like duration without dev-mode overhead.
            KernelEvents::RESPONSE => ['onResponse', 1024],
            // Logged after the response was sent to the client, so logging cost never hits the user.
            KernelEvents::TERMINATE => ['onTerminate', -4096],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set(self::REQUEST_START_ATTR, microtime(true));
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $start = $request->attributes->get(self::REQUEST_START_ATTR);
        if (!is_float($start)) {
            return;
        }

        $request->attributes->set(
            self::REQUEST_DURATION_ATTR,
            (int) ((microtime(true) - $start) * 1000)
        );
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $request = $event->getRequest();

        // Prefer the duration captured on kernel.response (prod-like, excludes profiler).
        // Fall back to terminate-time measurement when the response listener could not run.
        $durationMs = $request->attributes->get(self::REQUEST_DURATION_ATTR);
        if (!is_int($durationMs)) {
            $start = $request->attributes->get(self::REQUEST_START_ATTR);
            if (!is_float($start)) {
                return;
            }
            $durationMs = (int) ((microtime(true) - $start) * 1000);
        }

        if ($durationMs < $this->thresholdMs) {
            return;
        }

        $response = $event->getResponse();
        $route = $request->attributes->get('_route') ?? 'unknown';

        if (isset(self::EXCLUDED_ROUTES[$route])) {
            return;
        }

        // Profiler token is added by the WebProfilerBundle listener on kernel.response (priority -128).
        // We read it on kernel.terminate, which always runs after the profiler. Empty in prod.
        $profilerToken = $response->headers->get('X-Debug-Token');

        // GDPR: deliberately omits the client IP and query string so no personal data
        // can leak into the slow-requests log. Only the path is kept for diagnosis.
        $this->slowRequestLogger->warning('Slow request detected', [
            'duration_ms' => $durationMs,
            'method' => $request->getMethod(),
            'uri' => $request->getPathInfo(),
            'route' => $route,
            'area' => $this->resolveArea($request->getPathInfo(), (string) $route),
            'locale' => $request->getLocale(),
            'status' => $response->getStatusCode(),
            'peak_memory_mb' => (int) (memory_get_peak_usage(true) / 1048576),
            'host' => $request->getHost(),
            'profiler_token' => $profilerToken,
        ]);

        // Invalidate the dashboard stats cache so the next dashboard view sees this entry
        // immediately instead of waiting for the 5-minute TTL.
        try {
            $this->statsService->invalidate();
        } catch (\Throwable) {
            // Cache invalidation must never break a request. Silenced on purpose.
        }
    }

    /**
     * Classify a request as "admin" or "front" using cheap string checks (no service calls).
     */
    private function resolveArea(string $path, string $route): string
    {
        if (str_starts_with($route, 'admin_') || str_contains($path, '/admin-')) {
            return 'admin';
        }

        return 'front';
    }
}