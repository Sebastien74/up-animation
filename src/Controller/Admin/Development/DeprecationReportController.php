<?php

declare(strict_types=1);

namespace App\Controller\Admin\Development;

use App\Controller\Admin\AdminController;
use App\Service\Development\DeprecationCrawlService;
use App\Service\Development\DeprecationLogReportService;
use App\Service\Development\DeprecationScanService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DeprecationReportController.
 *
 * Internal maintenance page listing deprecations: the runtime journal (passive)
 * plus an on-demand complete static scan (PHPStan), run in AJAX batches.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/development/deprecations', schemes: '%protocol%')]
class DeprecationReportController extends AdminController
{
    #[Route('', name: 'admin_deprecation_report', methods: 'GET')]
    public function report(Request $request, DeprecationLogReportService $logReport, DeprecationScanService $scan, DeprecationCrawlService $crawl): Response
    {
        $this->breadcrumb($request, ['Rapport de dépréciations' => 'admin_deprecation_report']);

        $reportData = $logReport->getReport();
        $scanData = $scan->lastResults();
        $crawlData = $crawl->lastResults();

        $messages = array_merge(
            array_column($scanData['findings'], 'message'),
            array_column($crawlData['findings'], 'message'),
            array_column($reportData['items'], 'message'),
        );

        return $this->adminRender('admin/page/development/deprecation-report.html.twig', array_merge($this->arguments, [
            'pageTitle' => $this->coreLocator->translator()->trans('Rapport de dépréciations', [], 'admin'),
            'report' => $reportData,
            'scan' => $scanData,
            'scanFileCount' => $scan->fileCount(),
            'crawl' => $crawlData,
            'crawlUrlCount' => $crawl->urlCount(),
            'globalTotal' => \count($messages),
            'globalUnique' => \count(array_unique($messages)),
        ]));
    }

    #[Route('/scan', name: 'admin_deprecation_scan', methods: 'POST')]
    public function scan(Request $request, DeprecationScanService $scan): JsonResponse
    {
        if ('prod' === $this->getParameter('kernel.environment')) {
            throw $this->createNotFoundException();
        }

        $token = $request->headers->get('X-CSRF-Token', (string) $request->request->get('_token'));
        if (!$this->isCsrfTokenValid('deprecation_scan', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $offset = \is_array($payload) ? (int) ($payload['offset'] ?? 0) : 0;
        $size = \is_array($payload) ? (int) ($payload['size'] ?? 300) : 300;

        return new JsonResponse($scan->scanBatch($offset, $size));
    }

    #[Route('/crawl', name: 'admin_deprecation_crawl', methods: 'POST')]
    public function crawl(Request $request, DeprecationCrawlService $crawl): JsonResponse
    {
        if ('prod' === $this->getParameter('kernel.environment')) {
            throw $this->createNotFoundException();
        }

        $token = $request->headers->get('X-CSRF-Token', (string) $request->request->get('_token'));
        if (!$this->isCsrfTokenValid('deprecation_crawl', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $index = \is_array($payload) ? (int) ($payload['index'] ?? 0) : 0;

        return new JsonResponse($crawl->crawlOne($index));
    }

    #[Route('/clear', name: 'admin_deprecation_log_clear', methods: 'POST')]
    public function clear(Request $request, DeprecationLogReportService $logReport, DeprecationScanService $scan, DeprecationCrawlService $crawl): JsonResponse
    {
        if (!$this->isCsrfTokenValid('deprecation_clear', (string) $request->request->get('_token', $request->headers->get('X-CSRF-Token')))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(['success' => $logReport->clear() && $scan->clearScan() && $crawl->clearCrawl()]);
    }
}
