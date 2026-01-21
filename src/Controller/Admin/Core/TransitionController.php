<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Transition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TransitionController.
 *
 * Transition management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/website/transitions', schemes: '%protocol%')]
class TransitionController extends AdminController
{
    protected ?string $class = Transition::class;

    /**
     * Delete Transition.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{transition}', name: 'admin_transition_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
