<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Domain;
use App\Entity\Module\Search\SearchValue;
use App\Entity\Seo\NotFoundUrl;
use App\Entity\Seo\Url;
use App\Repository\Core\MailLogRepository;
use App\Service\Core\SlowRequestStatsService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DashboardController.
 *
 * Dashboard management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%', schemes: '%protocol%')]
class DashboardController extends AdminController
{
    /**
     * Dashboard view.
     */
    #[Route('/dashboard/{website}', name: 'admin_dashboard', defaults: ['website' => null], methods: 'GET')]
    public function view(
        Request $request,
        PaginatorInterface $paginator,
        SlowRequestStatsService $slowRequestStats,
        MailLogRepository $mailLogRepository,
    ): Response {
        $website = $this->getWebsite();
        $notFoundsLimit = 50;
        $domains = $this->coreLocator->em()->getRepository(Domain::class)->findByConfiguration($website->entity->getConfiguration());
        $notFoundRepository = $this->coreLocator->em()->getRepository(NotFoundUrl::class);
        $noSeoCounts = $this->coreLocator->em()->getRepository(Url::class)->countEmptyLocalesSEO($website->entity);
        $searchValues = $this->coreLocator->em()->getRepository(SearchValue::class)->findByWebsite($website->entity);
        $searchValues = $paginator->paginate(
            $searchValues,
            $request->query->getInt('page', 1),
            5,
            ['wrap-queries' => true]
        );
        $searchValues->setParam('_fragment', 'stats-search');

        // Performance stats are restricted to internal staff and parsed from cache.
        $performanceStats = $this->isGranted('ROLE_INTERNAL') ? $slowRequestStats->getStats() : null;

        $mailStats = [
            'counts' => $mailLogRepository->countByStatus(),
            'last24h' => $mailLogRepository->countLast24h(),
            'daily' => $mailLogRepository->countDaily(30),
        ];

        return $this->adminRender('admin/page/core/dashboard.html.twig', [
            'notFoundUrls' => $notFoundRepository->findFrontWithoutRedirections($website->entity, $domains, $notFoundsLimit),
            'notFoundCount' => $notFoundRepository->countFrontWithoutRedirections($website->entity, $domains),
            'notFoundsLimit' => $notFoundsLimit,
            'noSeoCounts' => $noSeoCounts,
            'searchValues' => $searchValues,
            'hasInstagramFeed' => (bool) $website->api?->instagram?->accessToken,
            'hasTikTokFeed' => (bool) $website->api?->tiktok?->accessToken,
            'performanceStats' => $performanceStats,
            'mailStats' => $mailStats,
        ]);
    }
}
