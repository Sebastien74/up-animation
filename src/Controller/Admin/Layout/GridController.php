<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Grid;
use App\Form\Type\Layout\Management\GridType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * GridController.
 *
 * Layout Grid management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/layouts/grids', schemes: '%protocol%')]
class GridController extends AdminController
{
    protected ?string $class = Grid::class;
    protected ?string $formType = GridType::class;

    /**
     * Index Grid.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_grid_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Grid.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_grid_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Grid.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{grid}', name: 'admin_grid_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Grid.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{grid}', name: 'admin_grid_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Grid.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{grid}', name: 'admin_grid_position', methods: 'GET')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Grid.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{grid}', name: 'admin_grid_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('grid')) {
            $items[$this->coreLocator->translator()->trans('Grilles', [], 'admin_breadcrumb')] = 'admin_grid_index';
        }

        parent::breadcrumb($request, $items);
    }
}
