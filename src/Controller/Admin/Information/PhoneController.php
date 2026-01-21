<?php

declare(strict_types=1);

namespace App\Controller\Admin\Information;

use App\Controller\Admin\AdminController;
use App\Entity\Information\Phone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * PhoneController.
 *
 * Information Phone management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/information/phones', schemes: '%protocol%')]
class PhoneController extends AdminController
{
    protected ?string $class = Phone::class;

    /**
     * Delete Phone.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{phone}', name: 'admin_phone_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Position Phone.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{phone}', name: 'admin_phone_position', methods: 'GET')]
    public function position(Request $request)
    {
        return parent::position($request);
    }
}
