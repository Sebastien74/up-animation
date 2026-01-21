<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Agenda;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Agenda\Information;
use App\Form\Type\Module\Agenda\InformationType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * InformationController.
 *
 * Agenda Information Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_AGENDA')]
#[Route('/admin-%security_token%/{website}/agendas/information', schemes: '%protocol%')]
class InformationController extends AdminController
{
    protected ?string $class = Information::class;
    protected ?string $formType = InformationType::class;

    /**
     * Index Information.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_agendainformation_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Information.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_agendainformation_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Information.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{agendainformation}', name: 'admin_agendainformation_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Information.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{agendainformation}', name: 'admin_agendainformation_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Information.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{agendainformation}', name: 'admin_agendainformation_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Information.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{agendainformation}', name: 'admin_agendainformation_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('agendainformation')) {
            $items[$this->coreLocator->translator()->trans('Informations', [], 'admin_breadcrumb')] = 'admin_agendainformation_index';
        }

        parent::breadcrumb($request, $items);
    }
}
