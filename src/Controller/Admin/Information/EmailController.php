<?php

declare(strict_types=1);

namespace App\Controller\Admin\Information;

use App\Controller\Admin\AdminController;
use App\Entity\Information\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * EmailController.
 *
 * Information Phone management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/information/emails', schemes: '%protocol%')]
class EmailController extends AdminController
{
    protected ?string $class = Email::class;

    /**
     * Delete Email.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{email}', name: 'admin_email_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Position Email.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{email}', name: 'admin_email_position', methods: 'GET')]
    public function position(Request $request)
    {
        return parent::position($request);
    }
}
