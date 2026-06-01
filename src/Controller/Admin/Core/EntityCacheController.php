<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Service\Core\EntityCacheInvalidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EntityCacheController.
 *
 * Per-entity cache invalidation from the edit view of layout-bearing entities.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/cache/entity', schemes: '%protocol%')]
class EntityCacheController extends AdminController
{
    private const string CSRF_TOKEN_ID = 'admin_entity_cache_invalidate';

    #[Route('/invalidate', name: 'admin_entity_cache_invalidate', methods: 'POST')]
    public function invalidate(
        Request $request,
        EntityCacheInvalidator $entityCacheInvalidator,
        TranslatorInterface $translator,
    ): RedirectResponse {
        $redirect = $this->redirect($request->headers->get('referer') ?: $this->generateUrl('admin_dashboard'));

        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            $this->addFlash('error', $translator->trans('Token CSRF invalide.', [], 'admin'));
            return $redirect;
        }

        $class = (string) $request->request->get('class');
        $id = $request->request->getInt('id');

        if ($id <= 0 || !class_exists($class) || !$this->coreLocator->em()->getMetadataFactory()->hasMetadataFor($class)) {
            $this->addFlash('error', $translator->trans('Entité invalide.', [], 'admin'));
            return $redirect;
        }

        $entity = $this->coreLocator->em()->getRepository($class)->find($id);
        if (null === $entity) {
            $this->addFlash('error', $translator->trans('Entité introuvable.', [], 'admin'));
            return $redirect;
        }

        $this->denyUnlessEntityWebsite($entity);

        if (!$entityCacheInvalidator->supports($entity)) {
            $this->addFlash('error', $translator->trans('Cette entité n\'a pas de layout à invalider.', [], 'admin'));
            return $redirect;
        }

        $entityCacheInvalidator->invalidate($entity);
        $this->addFlash('success', $translator->trans('Le cache de cette fiche a été invalidé.', [], 'admin'));

        return $redirect;
    }
}
