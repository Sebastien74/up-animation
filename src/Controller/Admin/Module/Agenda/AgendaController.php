<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Agenda;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Agenda\Agenda;
use App\Form\Type\Module\Agenda\AgendaType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * AgendaController.
 *
 * Agenda Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_AGENDA')]
#[Route('/admin-%security_token%/{website}/agendas', schemes: '%protocol%')]
class AgendaController extends AdminController
{
    protected ?string $class = Agenda::class;
    protected ?string $formType = AgendaType::class;

    /**
     * Index Agenda.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_agenda_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Agenda.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_agenda_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Agenda.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{agenda}', name: 'admin_agenda_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Position Agenda.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{agenda}', name: 'admin_agenda_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Agenda.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{agenda}', name: 'admin_agenda_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('agenda')) {
            $items[$this->coreLocator->translator()->trans('Agendas', [], 'admin_breadcrumb')] = 'admin_agenda_index';
        }

        parent::breadcrumb($request, $items);
    }
}
