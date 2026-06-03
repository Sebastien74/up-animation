<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Service\Core\SlowRequestStatsService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * PerformanceController.
 *
 * Inspector page that exposes captured slow-request entries to internal staff.
 * Restricted to ROLE_INTERNAL because the data is operational diagnostics, not
 * editorial content.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%', schemes: '%protocol%')]
final class PerformanceController extends AdminController
{
    private const int ENTRIES_LIMIT = 200;
    private const int PAGE_SIZE = 20;

    #[Route(
        '/performance/{area}/{website}',
        name: 'admin_performance_inspector',
        requirements: ['area' => 'front|admin'],
        defaults: ['website' => null],
        methods: 'GET'
    )]
    public function inspector(
        Request $request,
        string $area,
        SlowRequestStatsService $stats,
        PaginatorInterface $paginator,
    ): Response {
        $entries = $stats->getEntries($area, self::ENTRIES_LIMIT);
        $aggregate = $this->aggregate($entries);

        $pagination = $paginator->paginate(
            $entries,
            $request->query->getInt('page', 1),
            self::PAGE_SIZE
        );

        $label = 'front' === $area
            ? $this->coreLocator->translator()->trans('Requêtes lentes - Front', [], 'admin')
            : $this->coreLocator->translator()->trans('Requêtes lentes - Back-office', [], 'admin');
        // Pre-generate the URL because admin_performance_inspector requires the {area} placeholder,
        // which the parent breadcrumb resolver does not know how to fill from default args.
        $breadcrumbUrl = $this->coreLocator->router()->generate('admin_performance_inspector', [
            'area' => $area,
            'website' => $request->attributes->get('website'),
        ]);
        $this->breadcrumb($request, [$label => $breadcrumbUrl]);

        return $this->adminRender('admin/page/core/performance.html.twig', array_merge($this->arguments, [
            'area' => $area,
            'pagination' => $pagination,
            'entriesLimit' => self::ENTRIES_LIMIT,
            'aggregate' => $aggregate,
        ]));
    }

    /**
     * Lightweight aggregation over the entries already loaded - no second scan of disk.
     *
     * @param list<array{duration_ms:int,peak_memory_mb:int,route:string}> $entries
     */
    private function aggregate(array $entries): array
    {
        $count = count($entries);
        if (0 === $count) {
            return [
                'count' => 0,
                'avg_duration_ms' => 0,
                'max_duration_ms' => 0,
                'avg_memory_mb' => 0,
                'max_memory_mb' => 0,
                'top_route' => null,
                'top_route_count' => 0,
            ];
        }

        $totalDuration = 0;
        $maxDuration = 0;
        $totalMemory = 0;
        $maxMemory = 0;
        $routes = [];

        foreach ($entries as $entry) {
            $totalDuration += $entry['duration_ms'];
            $maxDuration = max($maxDuration, $entry['duration_ms']);
            $totalMemory += $entry['peak_memory_mb'];
            $maxMemory = max($maxMemory, $entry['peak_memory_mb']);
            $uri = $entry['uri'] ?: 'unknown';
            $routes[$uri] = ($routes[$uri] ?? 0) + 1;
        }

        arsort($routes);
        $topRoute = array_key_first($routes);

        return [
            'count' => $count,
            'avg_duration_ms' => (int) round($totalDuration / $count),
            'max_duration_ms' => $maxDuration,
            'avg_memory_mb' => (int) round($totalMemory / $count),
            'max_memory_mb' => $maxMemory,
            'top_route' => $topRoute,
            'top_route_count' => $topRoute ? $routes[$topRoute] : 0,
        ];
    }
}
