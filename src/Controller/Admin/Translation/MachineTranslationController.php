<?php

declare(strict_types=1);

namespace App\Controller\Admin\Translation;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Service\Translation\ExportService;
use App\Service\Translation\MachineTranslationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MachineTranslationController.
 *
 * "Translate everything": fills missing translations (intl + translation keys)
 * through the free provider chain, driven by AJAX batches with a progress bar.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/translations/translate', schemes: '%protocol%')]
class MachineTranslationController extends AdminController
{
    /**
     * Build the batches to translate and render the progress UI.
     */
    #[Route('/progress', name: 'admin_translation_translate_progress', options: ['expose' => true], methods: 'GET')]
    public function progress(Website $website, ExportService $exportService): JsonResponse
    {
        $groups = $exportService->collectTranslatable($website);
        $total = array_sum(array_map(static fn (array $group): int => \count($group['items']), $groups));

        return new JsonResponse([
            'html' => $this->renderView('admin/page/translation/translate-progress.html.twig', [
                'groups' => $groups,
                'total' => $total,
            ]),
            'total' => $total,
        ]);
    }

    /**
     * Translate and persist a single batch.
     */
    #[Route('/batch', name: 'admin_translation_translate_batch', options: ['expose' => true], methods: 'POST')]
    public function batch(Request $request, Website $website, MachineTranslationService $service): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-Token', (string) $request->request->get('_token'));
        if (!$this->isCsrfTokenValid('machine_translate', $token)) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid payload.'], Response::HTTP_BAD_REQUEST);
        }

        $count = $service->translateAndPersist($website, $payload);

        return new JsonResponse(['success' => true, 'count' => $count]);
    }
}
