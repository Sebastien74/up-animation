<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\GridCol;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * GridColController.
 *
 * Layout GridCol management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/layouts/grid-cols', schemes: '%protocol%')]
class GridColController extends AdminController
{
    protected ?string $class = GridCol::class;

    /**
     * Delete GridCol.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{gridcol}', name: 'admin_gridcol_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
