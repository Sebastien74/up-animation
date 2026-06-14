<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Service\Admin\PageAnalysisRecorder;
use App\Service\Admin\PageAnalyzerInterface;

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
    private function analyzePreview(PageAnalyzerInterface $analyzer, PageAnalysisRecorder $recorder, string $interface, Website $website, Url $url): array
    {
        $request = $this->coreLocator->request();
        if ($request) {
            $request->setLocale((string) $url->getLocale());
        }

        $controller = self::PREVIEW_CONTROLLERS[$interface] ?? self::PREVIEW_CONTROLLERS['page'];
        $arguments = 'page' === $interface ? ['website' => $website, 'url' => $url] : ['url' => $url];

        $start = microtime(true);
        $response = $this->forward($controller, $arguments);
        $report = $analyzer->analyze((string) $response->getContent(), $url->getCode());
        $report['meta']['renderMs'] = (int) round((microtime(true) - $start) * 1000);

        $recorder->record($website, $url->getCode(), $url->getLocale(), $report, 'manual');

        return $report;
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
