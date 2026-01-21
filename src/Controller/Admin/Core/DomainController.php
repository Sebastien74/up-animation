<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Domain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DomainController.
 *
 * DomainModel management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/website/domains', schemes: '%protocol%')]
class DomainController extends AdminController
{
    protected ?string $class = Domain::class;

    /**
     * Delete DomainModel.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{domain}', name: 'admin_domain_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
