<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Service\Admin\PageAnalysisRecorder;
use App\Service\Admin\PageAnalyzerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * PageAnalysisTrait.
 *
 * Shared logic to render a front page in preview mode (per interface) and analyze it.
 * Used by the layout edit modal and the analysis dashboard.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
trait PageAnalysisTrait
{
    /**
     * Front preview controllers per interface name. Each renders the page in preview
     * mode (admin only) and returns the full HTML response.
     */
    private const PREVIEW_CONTROLLERS = [
        'page' => 'App\Controller\Front\IndexController::preview',
        'newscast' => 'App\Controller\Front\Action\NewscastController::preview',
        'catalogproduct' => 'App\Controller\Front\Action\CatalogController::preview',
    ];

    /**
     * Front preview route names per interface (for "open preview" links).
     */
    private const PREVIEW_ROUTES = [
        'page' => 'front_page_preview',
        'newscast' => 'front_newscast_preview',
        'catalogproduct' => 'front_catalogproduct_preview',
    ];

    /**
     * Render the page in preview, analyze the HTML, persist a snapshot and return the report.
     *
     * @return array{meta: array<string, mixed>, score: int|null, summary: array<string, int>, groups: array<int, array<string, mixed>>}
     */
    private function analyzePreview(PageAnalyzerInterface $analyzer, PageAnalysisRecorder $recorder, EventDispatcherInterface $dispatcher, string $interface, Website $website, Url $url): array
    {
        $request = $this->coreLocator->request();
        if ($request) {
            $request->setLocale((string) $url->getLocale());
            // Flag the render as a preview so front actions build view models (and not raw
            // entities), exactly as the public /preview path does. The analysis runs from an
            // /admin-<token>/ URL without "/preview", which the actions' heuristic would
            // otherwise treat as the admin back-office listing.
            $request->attributes->set('_pageAnalysisPreview', true);
        }

        $controller = self::PREVIEW_CONTROLLERS[$interface] ?? self::PREVIEW_CONTROLLERS['page'];
        $arguments = 'page' === $interface ? ['website' => $website, 'url' => $url] : ['url' => $url];

        // The preview renders through nested sub-requests (each catch=true), so a render
        // error is swallowed into a 500 page rather than thrown here. A one-shot listener
        // captures the first exception wherever it fires so its origin can be reported.
        $captured = null;
        $listener = static function (ExceptionEvent $event) use (&$captured): void {
            $captured ??= $event->getThrowable();
        };
        $dispatcher->addListener(KernelEvents::EXCEPTION, $listener, 2048);

        $start = microtime(true);
        $response = null;
        $status = 200;
        try {
            $response = $this->forward($controller, $arguments);
            $status = $response->getStatusCode();
        } catch (\Throwable $e) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $captured ??= $e;
        } finally {
            $dispatcher->removeListener(KernelEvents::EXCEPTION, $listener);
        }

        $errorDetail = null !== $captured ? $this->describeThrowable($captured) : null;
        if (null === $errorDetail && null !== $response && $status >= 400) {
            $errorDetail = $this->extractError((string) $response->getContent());
        }

        $report = (null === $response || $status >= 400)
            ? $analyzer->httpError($status, $errorDetail)
            : $analyzer->analyze((string) $response->getContent(), $url->getCode());
        $report['meta']['renderMs'] = (int) round((microtime(true) - $start) * 1000);
        $report['meta']['httpStatus'] = $status;

        $recorder->record($website, $url->getCode(), $url->getLocale(), $report, 'manual');

        return $report;
    }

    /**
     * Short description of a caught throwable for the error finding (full location in debug).
     */
    private function describeThrowable(\Throwable $e): string
    {
        $detail = sprintf('%s : %s', (new \ReflectionClass($e))->getShortName(), $e->getMessage());
        if ($this->coreLocator->isDebug()) {
            $detail .= sprintf(' (%s:%d)', basename($e->getFile()), $e->getLine());
        }

        return $detail;
    }

    /**
     * Best-effort origin extracted from a rendered error page (dev exposes the exception
     * in the document <title>); null when nothing usable is found.
     */
    private function extractError(string $html): ?string
    {
        if (1 === preg_match('#<title>(.*?)</title>#is', $html, $matches)) {
            $title = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5));
            if ('' !== $title) {
                return $title;
            }
        }

        return null;
    }

    /**
     * Generate the public preview URL for an interface/url (admin only).
     */
    private function previewUrlFor(string $interface, Website $website, Url $url): string
    {
        return $this->previewUrlForId($interface, $website, (int) $url->getId());
    }

    /**
     * Generate the public preview URL from a Url id (avoids hydrating the entity).
     */
    private function previewUrlForId(string $interface, Website $website, int $urlId): string
    {
        $route = self::PREVIEW_ROUTES[$interface] ?? self::PREVIEW_ROUTES['page'];
        $params = 'page' === $interface
            ? ['website' => $website->getId(), 'url' => $urlId]
            : ['url' => $urlId];

        return $this->coreLocator->router()->generate($route, $params);
    }
}
