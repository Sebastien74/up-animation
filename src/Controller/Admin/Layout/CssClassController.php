<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\CssClass;
use App\Entity\Layout\Zone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CssClassController.
 *
 * Layout CSS Zone management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_EDIT')]
#[Route('/admin-%security_token%/{website}/layouts/css-classes', schemes: '%protocol%')]
class CssClassController extends AdminController
{
    protected ?string $class = Zone::class;

    /**
     * Delete CssClass.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{cssclass}', name: 'admin_cssclass_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $this->class = CssClass::class;
        return parent::delete($request);
    }
}
