<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Service\Content\Feed\FeedSyncService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * FeedSyncController.
 *
 * refonte-admin endpoint to trigger an immediate sync of social feeds
 * (Instagram, TikTok). Clears the 12 h auto-sync lock first.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%', schemes: '%protocol%')]
class FeedSyncController extends AdminController
{
    private const string CSRF_TOKEN_ID = 'admin_feed_sync';

    #[Route('/feed/sync/{website}', name: 'admin_feed_sync', defaults: ['website' => null], methods: 'POST')]
    public function sync(
        Request $request,
        FeedSyncService $feedSyncService,
        TranslatorInterface $translator,
    ): RedirectResponse {
        $website = $this->getWebsite();

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));
            return $this->redirectToRoute('admin_dashboard', ['website' => $website->id]);
        }

        try {
            $results = $feedSyncService->sync();
            $this->addFlash('success', $this->formatStats($results, $translator));
        } catch (Throwable $e) {
            $this->addFlash('error', $translator->trans('Erreur lors de la synchronisation : %message%', [
                '%message%' => $e->getMessage(),
            ], 'admin'));
        }

        return $this->redirectToRoute('admin_dashboard', ['website' => $website->id]);
    }

    /**
     * @param array<string, array{added: int, updated: int, removed: int, mediaDownloaded: int}> $results
     */
    private function formatStats(array $results, TranslatorInterface $translator): string
    {
        if ($results === []) {
            return $translator->trans('Aucun provider à synchroniser.', [], 'admin');
        }

        $lines = [$translator->trans('Synchronisation terminée :', [], 'admin')];
        foreach ($results as $provider => $stats) {
            $lines[] = sprintf(
                '%s - +%d, ~%d, -%d, %d médias',
                ucfirst($provider),
                $stats['added'],
                $stats['updated'],
                $stats['removed'],
                $stats['mediaDownloaded']
            );
        }
        return implode(' | ', $lines);
    }
}
