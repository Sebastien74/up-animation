<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FrontCacheController.
 *
 * Front Cache management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/core/cache', schemes: '%protocol%')]
class FrontCacheController extends AdminController
{
    /**
     * Clear cache files.
     */
    #[Route('/clear-files', name: 'admin_clear_front_cache', methods: 'GET|DELETE')]
    public function clearCache(Request $request): JsonResponse
    {
        $this->coreLocator->cacheService()->clearCaches($this->getWebsite());
        if ($request->get('referer')) {
            $session = new Session();
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Cache supprimé avec succès !!', [], 'admin'));
        }

        return new JsonResponse(['success' => true, 'reload' => true]);
    }
}
