<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Form;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Form\Calendar;
use App\Entity\Module\Form\Form;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Form\CalendarType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CalendarController.
 *
 * Form Calendar Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_FORM_CALENDAR')]
#[Route('/admin-%security_token%/{website}/forms/calendars', schemes: '%protocol%')]
class CalendarController extends AdminController
{
    public const bool MULTIPLES_CALENDARS = true;

    protected ?string $class = Calendar::class;
    protected ?string $formType = CalendarType::class;

    /**
     * CatalogController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->formCalendar();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Calendar.
     *
     * {@inheritdoc}
     */
    #[Route('/{form}/index', name: 'admin_formcalendar_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        /** @var Form $form */
        $form = $this->coreLocator->em()->getRepository(Form::class)->find($request->get('form'));
        if (!self::MULTIPLES_CALENDARS && $form->getCalendars()->count() > 0) {
            $this->disableFormNew = true;
        }

        return parent::index($request, $paginator);
    }

    /**
     * New Calendar.
     *
     * {@inheritdoc}
     */
    #[Route('/{form}/new', name: 'admin_formcalendar_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Calendar.
     *
     * {@inheritdoc}
     */
    #[Route('/{form}/edit/{formcalendar}', name: 'admin_formcalendar_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        /** @var Calendar $calendar */
        $calendar = $this->coreLocator->em()->getRepository($this->class)->find($request->get('formcalendar'));
        $this->formManager->setSchedules($calendar);

        return parent::edit($request);
    }

    /**
     * Show Calendar.
     *
     * {@inheritdoc}
     */
    #[Route('/{form}/show/{formcalendar}', name: 'admin_formcalendar_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Calendar.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{formcalendar}', name: 'admin_formcalendar_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Calendar.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{formcalendar}', name: 'admin_formcalendar_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
