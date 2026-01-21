<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Map;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Map\Point;
use App\Form\Type\Module\Map\PointType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * PointController.
 *
 * Map Point Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MAP')]
#[Route('/admin-%security_token%/{website}/maps/points', schemes: '%protocol%')]
class PointController extends AdminController
{
    protected ?string $class = Point::class;
    protected ?string $formType = PointType::class;

    /**
     * Index Point.
     *
     * {@inheritdoc}
     */
    #[Route('/{map}/index', name: 'admin_mappoint_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Point.
     *
     * {@inheritdoc}
     */
    #[Route('/{map}/new', name: 'admin_mappoint_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Point.
     *
     * {@inheritdoc}
     */
    #[Route('/{map}/edit/{mappoint}', name: 'admin_mappoint_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Point.
     *
     * {@inheritdoc}
     */
    #[Route('/{map}/show/{mappoint}', name: 'admin_mappoint_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete Point.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{mappoint}', name: 'admin_mappoint_delete', methods: 'DELETE')]
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
            if ($request->get('mappoint')) {
                $items[$this->coreLocator->translator()->trans('Points', [], 'admin_breadcrumb')] = 'admin_mappoint_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
