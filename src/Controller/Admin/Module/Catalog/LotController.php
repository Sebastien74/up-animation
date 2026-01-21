<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Lot;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LotController.
 *
 * Catalog Lot management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_REAL_ESTATE_PROGRAM')]
#[Route('/admin-%security_token%/{website}/module/catalogs/lots', schemes: '%protocol%')]
class LotController extends AdminController
{
    protected ?string $class = Lot::class;

    /**
     * Position Lot.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{cataloglot}', name: 'admin_cataloglot_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        /** @var Lot $lot */
        $lot = $this->coreLocator->em()->getRepository($this->class)->find($request->get('cataloglot'));
        $lot->setPosition($request->get('position'));

        $this->coreLocator->em()->persist($lot);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Delete Lot.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{cataloglot}', name: 'admin_cataloglot_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
