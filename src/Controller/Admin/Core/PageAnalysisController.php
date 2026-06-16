<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Controller\Admin\PageAnalysisTrait;
use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Repository\Seo\PageAnalysisRepository;
use App\Repository\Seo\PageSpeedSnapshotRepository;
use App\Service\Admin\PageAnalysisMarkdownFormatter;
use App\Service\Admin\PageAnalysisRecorder;
use App\Service\Admin\PageAnalyzerInterface;
use App\Service\Admin\PageSpeedMarkdownFormatter;
use App\Service\Seo\PageSpeed\PageSpeedClient;
use App\Service\Seo\PageSpeed\PageSpeedException;
use App\Service\Seo\PageSpeed\PageSpeedRecorder;
use App\Service\Seo\PageSpeed\PublicPageUrlResolver;
use App\Service\Seo\PageSpeed\QuotaGuard;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PageAnalysisController.
 *
 * Dashboard to batch-analyze (in preview) all front pages of the Page, Newscast and
 * Product interfaces. Analysis runs are admin-only (ROLE_ADMIN) and rendered with
 * preview=true: they never affect public front navigation.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/analysis-page', schemes: '%protocol%')]
class PageAnalysisController extends AdminController
{
    use PageAnalysisTrait;

    /**
     * Analyzable interfaces: name => entity FQCN.
     */
    private const array INTERFACES = [
        'page' => 'App\Entity\Layout\Page',
        'newscast' => 'App\Entity\Module\Newscast\Newscast',
        'catalogproduct' => 'App\Entity\Module\Catalog\Product',
    ];

    private const int MAX_PER_INTERFACE = 500;

    private const string CSRF_DELETE = 'pa-delete';

    /**
     * List all front pages (Page, Newscast, Product) with their last indicative score.
     */
    #[Route('/dashboard', name: 'admin_page_analysis_dashboard', methods: 'GET')]
    public function dashboard(Request $request, Website $website, PageAnalysisRepository $analysisRepository, PageSpeedSnapshotRepository $pageSpeedRepository, PageSpeedClient $pageSpeed, QuotaGuard $quota): Response
    {
        $psiEnabled = $pageSpeed->isEnabled();
        $latest = $analysisRepository->findLatestPerPage($website);
        $latestPsi = $psiEnabled ? $pageSpeedRepository->findLatestPerPage($website) : [];
        $router = $this->coreLocator->router();
        $em = $this->coreLocator->em();
        $rows = [];

        foreach (self::INTERFACES as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            // Only the Page entity carries the home flag (asIndex).
            $indexSelect = 'page' === $name ? ', e.asIndex AS isIndex' : '';

            try {
                // One scalar query per interface (JOIN on urls), no entity/collection
                // hydration and no N+1.
                $records = $em->createQuery(
                    sprintf(
                        'SELECT e.adminName AS title, u.code AS code, u.locale AS locale, u.id AS urlId%s '
                        .'FROM %s e JOIN e.urls u WHERE e.website = :website AND u.online = true ORDER BY e.id DESC',
                        $indexSelect,
                        $class,
                    )
                )
                    ->setParameter('website', $website)
                    ->setMaxResults(self::MAX_PER_INTERFACE)
                    ->getArrayResult();
            } catch (\Throwable) {
                continue;
            }

            foreach ($records as $record) {
                $code = (string) $record['code'];
                $locale = (string) $record['locale'];
                $urlId = (int) $record['urlId'];
                $snapshot = $latest[$code.'|'.$locale] ?? null;
                $psi = $latestPsi[$code.'|'.$locale] ?? null;
                $title = ltrim((string) $record['title'], '_');
                $rows[] = [
                    'interface' => $name,
                    'home' => 'page' === $name && !empty($record['isIndex']),
                    'title' => '' !== $title ? $title : ($code ?: '/'),
                    'code' => $code,
                    'locale' => $locale,
                    'score' => $snapshot['score'] ?? null,
                    'kb' => $snapshot['kb'] ?? null,
                    'high' => $snapshot['high'] ?? null,
                    'medium' => $snapshot['medium'] ?? null,
                    'low' => $snapshot['low'] ?? null,
                    'httpStatus' => $snapshot['httpStatus'] ?? null,
                    'date' => $snapshot['date'] ?? null,
                    'psiMobile' => $psi['perfMobile'] ?? null,
                    'psiDesktop' => $psi['perfDesktop'] ?? null,
                    'psiDate' => $psi['date'] ?? null,
                    'runUrl' => $router->generate('admin_page_analysis_run', [
                        'website' => $website->getId(),
                        'url' => $urlId,
                        'interface' => $name,
                    ]),
                    'psiUrl' => $router->generate('admin_page_analysis_psi', [
                        'website' => $website->getId(),
                        'url' => $urlId,
                        'interface' => $name,
                    ]),
                    'detailUrl' => $router->generate('admin_page_analysis_detail', [
                        'website' => $website->getId(),
                        'url' => $urlId,
                        'interface' => $name,
                    ]),
                    'clearUrl' => $router->generate('admin_page_analysis_clear_page', [
                        'website' => $website->getId(),
                        'url' => $urlId,
                    ]),
                    'previewUrl' => $this->previewUrlForId($name, $website, $urlId),
                ];
            }
        }

        // Home page (the Page flagged asIndex) goes first; the stable sort keeps every
        // other row in its existing order.
        usort($rows, static fn (array $a, array $b): int => ($b['home'] <=> $a['home']));

        // Distinct locales present, for the language filter on the index.
        $locales = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => strtolower((string) $row['locale']),
            $rows
        ))));
        sort($locales);

        $this->breadcrumb($request, ['Analyse des pages' => 'admin_page_analysis_dashboard']);

        return $this->render('admin/page/core/analysis-page-dashboard.html.twig', array_merge($this->arguments, [
            'rows' => $rows,
            'locales' => $locales,
            'psiEnabled' => $psiEnabled,
            'psiQuotaLimit' => $psiEnabled ? $quota->dailyLimit() : 0,
            'psiQuotaUsed' => $psiEnabled ? $quota->usedToday() : 0,
            'psiRemaining' => $psiEnabled ? $quota->remainingMeasurements() : 0,
            'psiTotal' => count($rows),
        ]));
    }

    /**
     * Detail view for a single page: latest full report (grouped findings) and history.
     */
    #[Route('/detail/{url}', name: 'admin_page_analysis_detail', methods: 'GET')]
    public function detail(Request $request, Website $website, Url $url, PageAnalysisRepository $analysisRepository, PageAnalyzerInterface $analyzer, PageAnalysisRecorder $recorder, EventDispatcherInterface $dispatcher, PageAnalysisMarkdownFormatter $markdownFormatter, PageSpeedMarkdownFormatter $psiMarkdownFormatter, PageSpeedSnapshotRepository $pageSpeedRepository, PageSpeedClient $pageSpeed, QuotaGuard $quota): Response
    {
        $interface = (string) $request->query->get('interface', 'page');

        // Arriving from "Analyser la page" runs a fresh analysis, then redirects to the
        // clean URL (PRG) so a reload does not re-run and an HTTP error gets recorded/shown.
        if ($request->query->getBoolean('run')) {
            try {
                $this->analyzePreview($analyzer, $recorder, $dispatcher, $interface, $website, $url);
            } catch (\Throwable) {
            }

            return $this->redirectToRoute('admin_page_analysis_detail', [
                'website' => $website->getId(),
                'url' => $url->getId(),
                'interface' => $interface,
            ]);
        }

        $history = $analysisRepository->findLatestSnapshots($website, $url->getCode(), $url->getLocale(), 12);
        $latest = $history[0] ?? null;
        $name = $this->pageNameForUrl($interface, (int) $url->getId());

        $reportMarkdown = null !== $latest && is_array($latest->getReport())
            ? $markdownFormatter->format($latest->getReport(), $url->getCode() ?: '/', $name)
            : null;

        $detailUrl = $this->coreLocator->router()->generate('admin_page_analysis_detail', [
            'website' => $website->getId(),
            'url' => $url->getId(),
            'interface' => $interface,
        ]);
        $this->breadcrumb($request, [
            'Analyse des pages' => 'admin_page_analysis_dashboard',
            ($url->getCode() ?: '/') => $detailUrl,
        ]);

        $psiEnabled = $pageSpeed->isEnabled();
        $psiSnapshot = $psiEnabled ? $pageSpeedRepository->findLatestForPage($website, $url->getCode(), $url->getLocale()) : null;
        $psiMarkdown = null !== $psiSnapshot && is_array($psiSnapshot->getReport())
            ? $psiMarkdownFormatter->format($psiSnapshot->getReport(), $url->getCode() ?: '/', $name, $psiSnapshot->getCreatedAt())
            : null;

        return $this->render('admin/page/core/analysis-page-detail.html.twig', array_merge($this->arguments, [
            'url' => $url,
            'interface' => $interface,
            'name' => $name,
            'latest' => $latest,
            'history' => $history,
            'reportMarkdown' => $reportMarkdown,
            'psiEnabled' => $psiEnabled,
            'psiCanMeasure' => $psiEnabled && $quota->canMeasure(),
            'psiRemaining' => $psiEnabled ? $quota->remainingMeasurements() : 0,
            'psi' => $psiSnapshot,
            'psiMarkdown' => $psiMarkdown,
            'psiUrl' => $this->coreLocator->router()->generate('admin_page_analysis_psi', [
                'website' => $website->getId(),
                'url' => $url->getId(),
                'interface' => $interface,
            ]),
            'previewUrl' => $this->previewUrlFor($interface, $website, $url),
            'runUrl' => $this->coreLocator->router()->generate('admin_page_analysis_run', [
                'website' => $website->getId(),
                'url' => $url->getId(),
                'interface' => $interface,
            ]),
        ]));
    }

    /**
     * Delete every stored analysis of the current website.
     */
    #[Route('/clear', name: 'admin_page_analysis_clear', methods: 'POST')]
    public function clear(Request $request, Website $website, PageAnalysisRepository $analysisRepository, PageSpeedSnapshotRepository $pageSpeedRepository, TranslatorInterface $translator): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_DELETE, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));

            return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
        }

        $pageSpeedRepository->deleteAllForWebsite($website);
        $deleted = $analysisRepository->deleteAllForWebsite($website);
        $this->addFlash('success', $translator->trans('%count% analyse(s) supprimée(s).', ['%count%' => $deleted], 'admin'));

        return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
    }

    /**
     * Delete the stored analyses of a single page.
     */
    #[Route('/clear/{url}', name: 'admin_page_analysis_clear_page', methods: 'POST')]
    public function clearPage(Request $request, Website $website, Url $url, PageAnalysisRepository $analysisRepository, PageSpeedSnapshotRepository $pageSpeedRepository, TranslatorInterface $translator): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_DELETE, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));

            return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
        }

        $pageSpeedRepository->deleteForPage($website, $url->getCode(), $url->getLocale());
        $deleted = $analysisRepository->deleteForPage($website, $url->getCode(), $url->getLocale());
        $this->addFlash('success', $translator->trans('%count% analyse(s) supprimée(s).', ['%count%' => $deleted], 'admin'));

        $redirect = (string) $request->request->get('_redirect', '');
        if ('' !== $redirect
            && str_starts_with($redirect, '/')
            && !str_starts_with($redirect, '//')
            && !str_contains($redirect, '\\')) {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
    }

    /**
     * Human admin name of the page owning a url (scalar query, no entity hydration).
     */
    private function pageNameForUrl(string $interface, int $urlId): ?string
    {
        $class = self::INTERFACES[$interface] ?? null;
        if (null === $class || !class_exists($class)) {
            return null;
        }

        try {
            $rows = $this->coreLocator->em()->createQuery(
                sprintf('SELECT e.adminName AS title FROM %s e JOIN e.urls u WHERE u.id = :url', $class)
            )
                ->setParameter('url', $urlId)
                ->setMaxResults(1)
                ->getArrayResult();
        } catch (\Throwable) {
            return null;
        }

        $title = isset($rows[0]['title']) ? ltrim((string) $rows[0]['title'], '_') : '';

        return '' !== $title ? $title : null;
    }

    /**
     * Run the analysis for a single page (AJAX) and return its metrics as JSON.
     */
    #[Route('/run/{url}', name: 'admin_page_analysis_run', methods: 'POST')]
    public function run(Request $request, Website $website, Url $url, PageAnalyzerInterface $analyzer, PageAnalysisRecorder $recorder, EventDispatcherInterface $dispatcher): JsonResponse
    {
        $interface = (string) $request->query->get('interface', 'page');

        try {
            $report = $this->analyzePreview($analyzer, $recorder, $dispatcher, $interface, $website, $url);

            return new JsonResponse([
                'ok' => true,
                'score' => $report['score'] ?? null,
                'httpStatus' => $report['meta']['httpStatus'] ?? null,
                'kb' => $report['meta']['kb'] ?? 0,
                'requests' => $report['meta']['requests'] ?? 0,
                'high' => $report['summary']['high'] ?? 0,
                'medium' => $report['summary']['medium'] ?? 0,
                'low' => $report['summary']['low'] ?? 0,
                'renderMs' => $report['meta']['renderMs'] ?? 0,
                'date' => (new \DateTime('now'))->format('d/m/Y H:i'),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => $this->coreLocator->isDebug() ? $e->getMessage() : 'Erreur de rendu',
            ]);
        }
    }

    /**
     * Run a Google PageSpeed Insights test on the live public page (AJAX), record the
     * snapshot and return its headline scores as JSON. Slow by nature (real Lighthouse
     * runs at Google), so it is admin-triggered only, never on page load.
     */
    #[Route('/psi/{url}', name: 'admin_page_analysis_psi', methods: 'POST')]
    public function pageSpeed(Request $request, Website $website, Url $url, PageSpeedClient $client, PageSpeedRecorder $recorder, PublicPageUrlResolver $urlResolver, QuotaGuard $quota): JsonResponse
    {
        if (!$client->isEnabled()) {
            return new JsonResponse(['ok' => false, 'error' => 'PageSpeed Insights n\'est pas configuré.']);
        }

        // Hard guard against exceeding the daily API quota (the UI also disables the
        // buttons, but a request could still be replayed).
        if (!$quota->canMeasure()) {
            return new JsonResponse(['ok' => false, 'remaining' => 0, 'error' => 'Quota PageSpeed quotidien atteint.']);
        }

        // Newscasts and products do not live at "/{code}" but behind a module path, so the
        // resolver needs the owning entity to build the real public URL (as SEO does).
        $interface = (string) $request->query->get('interface', 'page');
        $class = self::INTERFACES[$interface] ?? null;
        $entity = null;
        if ('page' !== $interface && null !== $class && class_exists($class)) {
            try {
                $entity = $this->coreLocator->em()->createQuery(
                    sprintf('SELECT e FROM %s e JOIN e.urls u WHERE u.id = :url', $class)
                )
                    ->setParameter('url', $url->getId())
                    ->setMaxResults(1)
                    ->getOneOrNullResult();
            } catch (\Throwable) {
                $entity = null;
            }
        }

        $publicUrl = $urlResolver->resolve($website, $url, $interface, $entity, $class);
        if (null === $publicUrl) {
            return new JsonResponse(['ok' => false, 'error' => 'Aucun domaine public pour cette page.']);
        }

        try {
            $report = $client->measure($publicUrl, $url->getLocale());
            $quota->consumeMeasurement();
            $recorder->record($website, $url->getCode(), $url->getLocale(), $report);

            $mobile = $report['strategies']['mobile'] ?? null;
            $desktop = $report['strategies']['desktop'] ?? null;

            return new JsonResponse([
                'ok' => true,
                'perfMobile' => $mobile['scores']['performance'] ?? null,
                'perfDesktop' => $desktop['scores']['performance'] ?? null,
                'remaining' => $quota->remainingMeasurements(),
                'date' => (new \DateTime('now'))->format('d/m/Y H:i'),
            ]);
        } catch (PageSpeedException $e) {
            return new JsonResponse(['ok' => false, 'error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => $this->coreLocator->isDebug() ? $e->getMessage() : 'Erreur PageSpeed.',
            ]);
        }
    }
}
