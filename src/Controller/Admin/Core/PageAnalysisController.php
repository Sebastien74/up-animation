<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Controller\Admin\PageAnalysisTrait;
use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use App\Repository\Seo\PageAnalysisRepository;
use App\Service\Admin\PageAnalysisRecorder;
use App\Service\Admin\PageAnalyzerInterface;
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
#[Route('/admin-%security_token%/{website}/page-analysis', schemes: '%protocol%')]
class PageAnalysisController extends AdminController
{
    use PageAnalysisTrait;

    /**
     * Analyzable interfaces: name => entity FQCN.
     */
    private const INTERFACES = [
        'page' => 'App\Entity\Layout\Page',
        'newscast' => 'App\Entity\Module\Newscast\Newscast',
        'catalogproduct' => 'App\Entity\Module\Catalog\Product',
    ];

    private const MAX_PER_INTERFACE = 500;

    private const CSRF_DELETE = 'pa-delete';

    /**
     * List all front pages (Page, Newscast, Product) with their last indicative score.
     */
    #[Route('/dashboard', name: 'admin_page_analysis_dashboard', methods: 'GET')]
    public function dashboard(Request $request, Website $website, PageAnalysisRepository $analysisRepository): Response
    {
        $latest = $analysisRepository->findLatestPerPage($website);
        $router = $this->coreLocator->router();
        $em = $this->coreLocator->em();
        $rows = [];

        foreach (self::INTERFACES as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            try {
                // One scalar query per interface (JOIN on urls), no entity/collection
                // hydration and no N+1.
                $records = $em->createQuery(
                    sprintf(
                        'SELECT e.adminName AS title, u.code AS code, u.locale AS locale, u.id AS urlId '
                        .'FROM %s e JOIN e.urls u WHERE e.website = :website AND u.online = true ORDER BY e.id DESC',
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
                $title = ltrim((string) $record['title'], '_');
                $rows[] = [
                    'interface' => $name,
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
                    'runUrl' => $router->generate('admin_page_analysis_run', [
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

        $this->breadcrumb($request, ['Analyse des pages' => 'admin_page_analysis_dashboard']);

        return $this->render('admin/page/core/page-analysis-dashboard.html.twig', array_merge($this->arguments, [
            'rows' => $rows,
        ]));
    }

    /**
     * Detail view for a single page: latest full report (grouped findings) and history.
     */
    #[Route('/detail/{url}', name: 'admin_page_analysis_detail', methods: 'GET')]
    public function detail(Request $request, Website $website, Url $url, PageAnalysisRepository $analysisRepository): Response
    {
        $interface = (string) $request->query->get('interface', 'page');
        $history = $analysisRepository->findLatestSnapshots($website, $url->getCode(), $url->getLocale(), 12);
        $latest = $history[0] ?? null;

        $detailUrl = $this->coreLocator->router()->generate('admin_page_analysis_detail', [
            'website' => $website->getId(),
            'url' => $url->getId(),
            'interface' => $interface,
        ]);
        $this->breadcrumb($request, [
            'Analyse des pages' => 'admin_page_analysis_dashboard',
            ($url->getCode() ?: '/') => $detailUrl,
        ]);

        return $this->render('admin/page/core/page-analysis-detail.html.twig', array_merge($this->arguments, [
            'url' => $url,
            'interface' => $interface,
            'latest' => $latest,
            'history' => $history,
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
    public function clear(Request $request, Website $website, PageAnalysisRepository $analysisRepository, TranslatorInterface $translator): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_DELETE, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));

            return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
        }

        $deleted = $analysisRepository->deleteAllForWebsite($website);
        $this->addFlash('success', $translator->trans('%count% analyse(s) supprimée(s).', ['%count%' => $deleted], 'admin'));

        return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
    }

    /**
     * Delete the stored analyses of a single page.
     */
    #[Route('/clear/{url}', name: 'admin_page_analysis_clear_page', methods: 'POST')]
    public function clearPage(Request $request, Website $website, Url $url, PageAnalysisRepository $analysisRepository, TranslatorInterface $translator): RedirectResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_DELETE, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));

            return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
        }

        $deleted = $analysisRepository->deleteForPage($website, $url->getCode(), $url->getLocale());
        $this->addFlash('success', $translator->trans('%count% analyse(s) supprimée(s).', ['%count%' => $deleted], 'admin'));

        return $this->redirectToRoute('admin_page_analysis_dashboard', ['website' => $website->getId()]);
    }

    /**
     * Run the analysis for a single page (AJAX) and return its metrics as JSON.
     */
    #[Route('/run/{url}', name: 'admin_page_analysis_run', methods: 'POST')]
    public function run(Request $request, Website $website, Url $url, PageAnalyzerInterface $analyzer, PageAnalysisRecorder $recorder): JsonResponse
    {
        $interface = (string) $request->query->get('interface', 'page');

        try {
            $report = $this->analyzePreview($analyzer, $recorder, $interface, $website, $url);

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
}
