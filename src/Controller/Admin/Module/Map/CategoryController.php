<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Map;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Map\Category;
use App\Form\Type\Module\Map\CategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CategoryController.
 *
 * Map Category Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_MAP')]
#[Route('/admin-%security_token%/{website}/maps/categories', schemes: '%protocol%')]
class CategoryController extends AdminController
{
    protected ?string $class = Category::class;
    protected ?string $formType = CategoryType::class;

    /**
     * Index Category.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_mapcategory_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Category.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_mapcategory_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Category.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{mapcategory}', name: 'admin_mapcategory_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Category.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{mapcategory}', name: 'admin_mapcategory_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Category.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{mapcategory}', name: 'admin_mapcategory_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Category.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{mapcategory}', name: 'admin_mapcategory_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('mapcategory')) {
            $items[$this->coreLocator->translator()->trans('Catégories', [], 'admin_breadcrumb')] = 'admin_mapcategory_index';
        }

        parent::breadcrumb($request, $items);
    }
}
