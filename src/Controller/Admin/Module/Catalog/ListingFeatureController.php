<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\ListingFeatureValue;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ListingFeatureController.
 *
 * Catalog ListingFeature management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/listings/features', schemes: '%protocol%')]
class ListingFeatureController extends AdminController
{
    protected ?string $class = ListingFeatureValue::class;

    /**
     * Delete ListingFeature.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{cataloglistingfeature}', name: 'admin_cataloglistingfeature_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
