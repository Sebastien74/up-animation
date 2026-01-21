<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Portfolio;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Portfolio\Category;
use App\Form\Type\Module\Portfolio\CategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CategoryController.
 *
 * Portfolio Category Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_PORTFOLIO')]
#[Route('/admin-%security_token%/{website}/portfolios/categories', schemes: '%protocol%')]
class CategoryController extends AdminController
{
    protected ?string $class = Category::class;
    protected ?string $formType = CategoryType::class;

    /**
     * Index Category.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_portfoliocategory_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Category.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_portfoliocategory_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Layout Category.
     *
     * {@inheritdoc}
     */
    #[Route('/layout/{portfoliocategory}', name: 'admin_portfoliocategory_layout', methods: 'GET|POST')]
    public function layout(Request $request)
    {
        return parent::layout($request);
    }

    /**
     * Show Category.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{portfoliocategory}', name: 'admin_portfoliocategory_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Category.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{portfoliocategory}', name: 'admin_portfoliocategory_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Category.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{portfoliocategory}', name: 'admin_portfoliocategory_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('portfoliocategory')) {
            $items[$this->coreLocator->translator()->trans('Catégories', [], 'admin_breadcrumb')] = 'admin_portfoliocategory_index';
        }

        parent::breadcrumb($request, $items);
    }
}
