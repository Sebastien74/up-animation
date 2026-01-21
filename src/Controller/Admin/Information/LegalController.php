<?php

declare(strict_types=1);

namespace App\Controller\Admin\Information;

use App\Controller\Admin\AdminController;
use App\Entity\Information\Legal;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LegalController.
 *
 * Information Legal management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/information/legals', schemes: '%protocol%')]
class LegalController extends AdminController
{
    protected ?string $class = Legal::class;

    /**
     * Delete Legal.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{legal}', name: 'admin_legal_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
