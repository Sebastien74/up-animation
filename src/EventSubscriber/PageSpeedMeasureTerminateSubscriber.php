<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Controller\Admin\Core\PageAnalysisController;
use App\Entity\Core\Website;
use App\Service\Seo\PageSpeed\PageSpeedClient;
use App\Service\Seo\PageSpeed\PageSpeedRecorder;
use App\Service\Seo\PageSpeed\QuotaGuard;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * PageSpeedMeasureTerminateSubscriber.
 *
 * Runs the actual PageSpeed Insights measurement after the HTTP response has
 * been sent. The admin AJAX request returns immediately, so the long Google
 * calls never block the request behind Varnish (no "Backend fetch failed").
 * A single-slot cache lock (set by the controller) is released here.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class PageSpeedMeasureTerminateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PageSpeedClient $client,
        private PageSpeedRecorder $recorder,
        private QuotaGuard $quota,
        private EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $job = $event->getRequest()->attributes->get('_psi_job');
        if (!is_array($job)) {
            return;
        }

        try {
            $website = $this->entityManager->getRepository(Website::class)->find($job['websiteId']);
            if (!$website instanceof Website) {
                return;
            }
            $report = $this->client->measure($job['publicUrl'], $job['locale']);
            $this->quota->consumeMeasurement();
            $this->recorder->record($website, $job['code'], $job['locale'], $report);
        } catch (\Throwable $exception) {
            $this->logger->error('PageSpeed measurement failed', ['exception' => $exception, 'url' => $job['publicUrl'] ?? null]);
        } finally {
            try {
                $this->cache->deleteItem(PageAnalysisController::PSI_LOCK_KEY);
            } catch (\Throwable) {
            }
        }
    }
}
