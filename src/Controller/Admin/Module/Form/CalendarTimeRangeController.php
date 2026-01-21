<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\CalendarTimeRange;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CalendarTimeRangeController.
 *
 * Form Calendar Time Range Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM_CALENDAR')]
#[Route('/admin-%security_token%/{website}/forms/time-ranges', schemes: '%protocol%')]
class CalendarTimeRangeController extends AdminController
{
    protected ?string $class = CalendarTimeRange::class;

    /**
     * Delete CalendarTimeRange.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{formcalendartimerange}', name: 'admin_formcalendartimerange_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
