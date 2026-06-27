<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Controller\Admin\PageAnalysisTrait;
use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Repository\Seo\PageSpeedSnapshotRepository;
use App\Service\Admin\PageSpeedMarkdownFormatter;
use App\Service\Seo\PageSpeed\PageSpeedClient;
use App\Service\Seo\PageSpeed\PageSpeedQueue;
use App\Service\Seo\PageSpeed\PublicPageUrlResolver;
use App\Service\Seo\PageSpeed\QuotaGuard;
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
 * Google PageSpeed Insights dashboard for every front page (Page, Newscast, Product).
 * Measurements run real Lighthouse at Google, so they are admin-only (ROLE_ADMIN) and
 * triggered explicitly, never on page load.
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
     * List all front pages (Page, Newscast, Product) with their latest PageSpeed scores.
     */
    #[Route('/dashboard', name: 'admin_page_analysis_dashboard', methods: 'GET')]
    public function dashboard(Request $request, Website $website, PageSpeedSnapshotRepository $pageSpeedRepository, PageSpeedClient $pageSpeed, QuotaGuard $quota): Response
    {
        $psiEnabled = $pageSpeed->isEnabled();
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
                $psi = $latestPsi[$code.'|'.$locale] ?? null;
                $title = ltrim((string) $record['title'], '_');
                $rows[] = [
                    'interface' => $name,
                    'home' => 'page' === $name && !empty($record['isIndex']),
                    'title' => '' !== $title ? $title : ($code ?: '/'),
                    'code' => $code,
                    'locale' => $locale,
                    'psiMobile' => $psi['perfMobile'] ?? null,
                    'psiDesktop' => $psi['perfDesktop'] ?? null,
                    'psiDate' => $psi['date'] ?? null,
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
            'defaultLocale' => strtolower((string) ($website->getConfiguration()?->getLocale() ?? '')),
            'psiEnabled' => $psiEnabled,
            'psiQuotaLimit' => $psiEnabled ? $quota->dailyLimit() : 0,
            'psiQuotaUsed' => $psiEnabled ? $quota->usedToday() : 0,
            'psiRemaining' => $psiEnabled ? $quota->remainingMeasurements() : 0,
            'psiTotal' => count($rows),
        ]));
    }

    /**
     * Detail view for a single page: latest PageSpeed Insights report.
     */
    #[Route('/detail/{url}', name: 'admin_page_analysis_detail', methods: 'GET')]
    public function detail(Request $request, Website $website, Url $url, PageSpeedMarkdownFormatter $psiMarkdownFormatter, PageSpeedSnapshotRepository $pageSpeedRepository, PageSpeedClient $pageSpeed, QuotaGuard $quota, PublicPageUrlResolver $urlResolver): Response
    {
        $interface = (string) $request->query->get('interface', 'page');
        $name = $this->pageNameForUrl($interface, (int) $url->getId());

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
            'crawlUrl' => $this->crawlUrl($urlResolver, $website, $url, $interface),
        ]));
    }

    /**
     * Absolute public URL PageSpeed measures for this page. Card interfaces (newscast,
     * product) need their owning entity to build the URL.
     */
    private function crawlUrl(PublicPageUrlResolver $urlResolver, Website $website, Url $url, string $interface): ?string
    {
        $class = self::INTERFACES[$interface] ?? null;
        $entity = null;
        if (null !== $class && class_exists($class)) {
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

        return $urlResolver->resolve($website, $url, $interface, $entity, $class);
    }

    /**
     * Delete every stored PageSpeed snapshot of the current website.
     */
    #[Route('/clear', name: 'admin_page_analysis_clear', methods: 'POST')]
    public function clear(Request $request, Website $website, PageSpeedSnapshotRepository $pageSpeedRepository, TranslatorInterface $translator): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_DELETE, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));

            return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
        }

        $deleted = $pageSpeedRepository->deleteAllForWebsite($website);
        $this->addFlash('success', $translator->trans('%count% analyse(s) supprimée(s).', ['%count%' => $deleted], 'admin'));

        return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
    }

    /**
     * Delete the stored PageSpeed snapshots of a single page.
     */
    #[Route('/clear/{url}', name: 'admin_page_analysis_clear_page', methods: 'POST')]
    public function clearPage(Request $request, Website $website, Url $url, PageSpeedSnapshotRepository $pageSpeedRepository, TranslatorInterface $translator): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_DELETE, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));

            return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
        }

        $deleted = $pageSpeedRepository->deleteForPage($website, $url->getCode(), $url->getLocale());
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
     * Run a Google PageSpeed Insights test on the live public page (AJAX), record the
     * snapshot and return its headline scores as JSON. Slow by nature (real Lighthouse
     * runs at Google), so it is admin-triggered only, never on page load.
     */
    #[Route('/psi/{url}', name: 'admin_page_analysis_psi', methods: 'POST')]
    public function pageSpeed(Request $request, Website $website, Url $url, PageSpeedClient $client, PublicPageUrlResolver $urlResolver, QuotaGuard $quota, PageSpeedQueue $queue): JsonResponse
    {
        if (!$client->isEnabled()) {
            return new JsonResponse(['ok' => false, 'error' => 'PageSpeed Insights n\'est pas configuré.']);
        }

        // Hard guard against exceeding the daily API quota (the UI also disables the
        // buttons, but a request could still be replayed).
        if (!$quota->canMeasure()) {
            return new JsonResponse(['ok' => false, 'remaining' => 0, 'error' => 'Quota PageSpeed quotidien atteint.']);
        }

        // Newscasts and products do not live at "/{code}" but behind a module path, and the
        // home page (asIndex) at the domain root: crawlUrl() builds the real public URL.
        $interface = (string) $request->query->get('interface', 'page');
        $publicUrl = $this->crawlUrl($urlResolver, $website, $url, $interface);
        if (null === $publicUrl) {
            return new JsonResponse(['ok' => false, 'error' => 'Aucun domaine public pour cette page.']);
        }

        // A measurement runs several Google Lighthouse calls (tens of seconds): doing it in
        // the web request would hold an FPM worker behind Varnish and 503 on shared hosting.
        // Queue the job; the cron-run app:pagespeed:run command measures off the worker pool.
        $queue->enqueue([
            'publicUrl' => $publicUrl,
            'locale' => $url->getLocale(),
            'websiteId' => $website->getId(),
            'code' => $url->getCode(),
        ]);

        return new JsonResponse([
            'ok' => true,
            'running' => true,
            'perfMobile' => null,
            'perfDesktop' => null,
            'remaining' => $quota->remainingMeasurements(),
            'date' => null,
        ]);
    }
}
