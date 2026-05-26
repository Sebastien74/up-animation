<?php

declare(strict_types=1);

namespace App\Controller\Admin\Analytics;

use App\Controller\Admin\AdminController;
use App\Message\Analytics\RollupRequestMessage;
use App\Repository\Analytics\AnalyticsDailyRepository;
use App\Repository\Analytics\AnalyticsEventRepository;
use App\Repository\Analytics\AnalyticsHourlyRepository;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * StatsController.
 *
 * Renders the analytics dashboard scoped to the current website and
 * exposes a single JSON endpoint that bundles every chart series.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%', schemes: '%protocol%')]
class StatsController extends AdminController
{
    private const int DEFAULT_RANGE_DAYS = 30;
    private const int MAX_RANGE_DAYS = 366;
    private const int TOP_LIMIT = 10;

    #[Route('/stats/{website}', name: 'admin_stats_view', defaults: ['website' => null], methods: 'GET')]
    public function view(Request $request, MessageBusInterface $messageBus): Response
    {
        $website = $this->getWebsite();

        if (!$website->configuration->enableAnalytics) {
            return $this->redirectToRoute('admin_dashboard', ['website' => $website->entity->getId()]);
        }

        $messageBus->dispatch(new RollupRequestMessage());

        parent::breadcrumb($request, ['Statistiques' => 'admin_stats_view']);

        return $this->adminRender('admin/page/analytics/stats.html.twig', array_merge($this->arguments, [
            'website' => $website,
            'rangeDefaultDays' => self::DEFAULT_RANGE_DAYS,
            'rangeMaxDays' => self::MAX_RANGE_DAYS,
            'allLocales' => $website->configuration->allLocales,
            'defaultLocale' => $website->configuration->locale,
        ]));
    }

    /**
     * @throws DBALException
     */
    #[Route('/stats/{website}/data.json', name: 'admin_stats_data', defaults: ['website' => null], methods: 'GET')]
    public function data(
        Request $request,
        AnalyticsDailyRepository $dailyRepository,
        AnalyticsHourlyRepository $hourlyRepository,
        AnalyticsEventRepository $eventRepository,
    ): JsonResponse {
        $website = $this->getWebsite();
        $websiteId = (int) $website->entity->getId();

        [$from, $to] = $this->resolveRange($request);
        $locale = $this->resolveLocale($request);

        $hourlyTo = $to->modify('+1 day');
        $eventCutoff = $from->modify('-1 day');

        return new JsonResponse([
            'range' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'locale' => $locale,
            'totals' => $dailyRepository->findTotals($websiteId, $from, $to, $locale),
            'series' => $dailyRepository->findSeries($websiteId, $from, $to, $locale),
            'topPages' => $dailyRepository->findBreakdown($websiteId, $from, $to, 'urlPath', 'pageviews', self::TOP_LIMIT, $locale),
            'countries' => $dailyRepository->findBreakdown($websiteId, $from, $to, 'countryCode', 'sessions', self::TOP_LIMIT, $locale),
            'devices' => $dailyRepository->findBreakdown($websiteId, $from, $to, 'device', 'sessions', self::TOP_LIMIT, $locale),
            'locales' => $dailyRepository->findBreakdown($websiteId, $from, $to, 'locale', 'sessions', self::TOP_LIMIT),
            'sources' => $eventRepository->findTopReferrers($websiteId, $eventCutoff, $to->modify('+1 day'), self::TOP_LIMIT, $locale),
            'clicks' => $eventRepository->findTopClicks($websiteId, $eventCutoff, $to->modify('+1 day'), self::TOP_LIMIT, $locale),
            'heatmap' => $hourlyRepository->findHeatmap($websiteId, $from, $hourlyTo, $locale),
        ]);
    }

    private function resolveLocale(Request $request): ?string
    {
        $raw = (string) $request->query->get('locale');
        if ('' === $raw) {
            return null;
        }
        $website = $this->getWebsite();
        $allowed = $website->configuration->allLocales ?? [];

        return in_array($raw, $allowed, true) ? $raw : null;
    }

    #[Route('/stats/{website}/data.csv', name: 'admin_stats_export', defaults: ['website' => null], methods: 'GET')]
    public function exportCsv(Request $request, AnalyticsDailyRepository $dailyRepository): StreamedResponse
    {
        $website = $this->getWebsite();
        $websiteId = (int) $website->entity->getId();
        [$from, $to] = $this->resolveRange($request);
        $locale = $this->resolveLocale($request);

        $filename = sprintf('analytics-%s-%s%s.csv', $from->format('Y-m-d'), $to->format('Y-m-d'), null !== $locale ? '-'.$locale : '');

        $response = new StreamedResponse(static function () use ($dailyRepository, $websiteId, $from, $to, $locale): void {
            $handle = fopen('php://output', 'w');
            if (false === $handle) {
                return;
            }
            fputcsv($handle, ['date', 'urlPath', 'countryCode', 'device', 'locale', 'visitors', 'sessions', 'pageviews']);
            foreach ($dailyRepository->streamRows($websiteId, $from, $to, $locale) as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['urlPath'],
                    $row['countryCode'] ?? '',
                    $row['device'] ?? '',
                    $row['locale'] ?? '',
                    $row['visitors'],
                    $row['sessions'],
                    $row['pageviews'],
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /**
     * @return array{0:\DateTimeImmutable, 1:\DateTimeImmutable}
     */
    private function resolveRange(Request $request): array
    {
        $tz = new \DateTimeZone('UTC');
        $to = $this->parseDate((string) $request->query->get('to'), $tz) ?? new \DateTimeImmutable('today', $tz);
        $from = $this->parseDate((string) $request->query->get('from'), $tz)
            ?? $to->modify('-'.(self::DEFAULT_RANGE_DAYS - 1).' days');

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $maxFrom = $to->modify('-'.(self::MAX_RANGE_DAYS - 1).' days');
        if ($from < $maxFrom) {
            $from = $maxFrom;
        }

        return [$from->setTime(0, 0), $to->setTime(0, 0)];
    }

    private function parseDate(string $raw, \DateTimeZone $tz): ?\DateTimeImmutable
    {
        if ('' === $raw || 1 !== preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $raw, $tz);

        return false === $date ? null : $date;
    }
}
