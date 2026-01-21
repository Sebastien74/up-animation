<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\CalendarException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CalendarExceptionController.
 *
 * Form Calendar Exception management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM_CALENDAR')]
#[Route('/admin-%security_token%/{website}/forms/exceptions', schemes: '%protocol%')]
class CalendarExceptionController extends AdminController
{
    protected ?string $class = CalendarException::class;

    /**
     * Delete CalendarException.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{formcalendarexception}', name: 'admin_formcalendarexception_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
