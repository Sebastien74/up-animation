<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Map;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Map\Map;
use App\Form\Type\Module\Map\MapType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MapController.
 *
 * Map Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MAP')]
#[Route('/admin-%security_token%/{website}/maps', schemes: '%protocol%')]
class MapController extends AdminController
{
    protected ?string $class = Map::class;
    protected ?string $formType = MapType::class;

    /**
     * Index Map.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_map_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Map.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_map_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Map.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{map}', name: 'admin_map_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Map.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{map}', name: 'admin_map_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Map.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{map}', name: 'admin_map_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Map.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{map}', name: 'admin_map_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('map')) {
            $items[$this->coreLocator->translator()->trans('Cartes', [], 'admin_breadcrumb')] = 'admin_map_index';
        }

        parent::breadcrumb($request, $items);
    }
}
