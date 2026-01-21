<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\CalendarAppointment;
use App\Form\Type\Module\Form\CalendarAppointmentType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CalendarAppointmentController.
 *
 * Form Calendar Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM_CALENDAR')]
#[Route('/admin-%security_token%/{website}/forms/calendars/appointments', schemes: '%protocol%')]
class CalendarAppointmentController extends AdminController
{
    protected ?string $class = CalendarAppointment::class;
    protected ?string $formType = CalendarAppointmentType::class;

    /**
     * Index CalendarAppointment.
     *
     * {@inheritdoc}
     */
    #[Route('/{formcalendar}/index', name: 'admin_formcalendarappointment_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * Edit CalendarAppointment.
     *
     * {@inheritdoc}
     */
    #[Route('/{formcalendar}/edit/{formcalendarappointment}', name: 'admin_formcalendarappointment_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show CalendarAppointment.
     *
     * {@inheritdoc}
     */
    #[Route('/{formcalendar}/show/{formcalendarappointment}', name: 'admin_formcalendarappointment_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Export CalendarAppointment[].
     *
     * {@inheritdoc}
     */
    #[Route('/export', name: 'admin_formcalendarappointment_export', methods: 'GET|POST')]
    public function export(Request $request)
    {
        return parent::export($request);
    }

    /**
     * Delete CalendarAppointment.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{formcalendarappointment}', name: 'admin_formcalendarappointment_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
