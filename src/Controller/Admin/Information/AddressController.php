<?php

declare(strict_types=1);

namespace App\Controller\Admin\Information;

use App\Controller\Admin\AdminController;
use App\Entity\Information\Address;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AddressController.
 *
 * Information Address management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/information/addresses', schemes: '%protocol%')]
class AddressController extends AdminController
{
    protected ?string $class = Address::class;

    /**
     * Delete Address.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{address}', name: 'admin_address_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Position Address.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{address}', name: 'admin_address_position', methods: 'GET')]
    public function position(Request $request)
    {
        return parent::position($request);
    }
}
