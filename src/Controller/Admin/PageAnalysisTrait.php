<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Core\Website;
use App\Entity\Seo\PageAnalysis;
use App\Entity\Seo\Url;
use App\Repository\Seo\PageAnalysisRepository;
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
    private function analyzePreview(PageAnalyzerInterface $analyzer, PageAnalysisRepository $repository, string $interface, Website $website, Url $url): array
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

        $this->saveSnapshot($repository, $website, $url, $report);

        return $report;
    }

    /**
     * Generate the public preview URL for an interface/url (admin only).
     */
    private function previewUrlFor(string $interface, Website $website, Url $url): string
    {
        $route = self::PREVIEW_ROUTES[$interface] ?? self::PREVIEW_ROUTES['page'];
        $params = 'page' === $interface
            ? ['website' => $website->getId(), 'url' => $url->getId()]
            : ['url' => $url->getId()];

        return $this->coreLocator->router()->generate($route, $params);
    }

    /**
     * Persist a page analysis snapshot. Best-effort: never blocks the report on a DB error.
     *
     * @param array<string, mixed> $report
     */
    private function saveSnapshot(PageAnalysisRepository $repository, Website $website, Url $url, array $report): void
    {
        try {
            $meta = $report['meta'] ?? [];
            $summary = $report['summary'] ?? [];
            $snapshot = (new PageAnalysis())
                ->setWebsite($website)
                ->setUrlCode((string) $url->getCode())
                ->setLocale((string) $url->getLocale())
                ->setScore($report['score'] ?? null)
                ->setHtmlKb((int) ($meta['kb'] ?? 0))
                ->setDomCount((int) ($meta['dom'] ?? 0))
                ->setImagesCount((int) ($meta['images'] ?? 0))
                ->setRequests((int) ($meta['requests'] ?? 0))
                ->setRenderMs(isset($meta['renderMs']) ? (int) $meta['renderMs'] : null)
                ->setExternalDomains((int) ($meta['externalDomains'] ?? 0))
                ->setSeverityHigh((int) ($summary['high'] ?? 0))
                ->setSeverityMedium((int) ($summary['medium'] ?? 0))
                ->setSeverityLow((int) ($summary['low'] ?? 0))
                ->setReport($report);

            $em = $this->coreLocator->em();
            $em->persist($snapshot);
            $em->flush();

            $repository->pruneOldSnapshots($website, $url->getCode(), $url->getLocale(), 20);
        } catch (\Throwable) {
            // Historization is best-effort.
        }
    }
}
