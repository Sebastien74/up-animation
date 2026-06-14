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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    /**
     * List all front pages (Page, Newscast, Product) with their last indicative score.
     */
    #[Route('/dashboard', name: 'admin_page_analysis_dashboard', methods: 'GET')]
    public function dashboard(Website $website, PageAnalysisRepository $analysisRepository): Response
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
                    'date' => $snapshot['date'] ?? null,
                    'runUrl' => $router->generate('admin_page_analysis_run', [
                        'website' => $website->getId(),
                        'url' => $urlId,
                        'interface' => $name,
                    ]),
                    'previewUrl' => $this->previewUrlForId($name, $website, $urlId),
                ];
            }
        }

        return $this->render('admin/page/core/page-analysis-dashboard.html.twig', [
            'rows' => $rows,
        ]);
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
