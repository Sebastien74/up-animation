<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Search;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Search\Search;
use App\Form\Type\Module\Search\SearchType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SearchController.
 *
 * Search Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SEARCH_ENGINE')]
#[Route('/admin-%security_token%/{website}/search', schemes: '%protocol%')]
class SearchController extends AdminController
{
    protected ?string $class = Search::class;
    protected ?string $formType = SearchType::class;

    /**
     * Index Search.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_search_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Search.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_search_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Search.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{search}', name: 'admin_search_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Search.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{search}', name: 'admin_search_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Search.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{search}', name: 'admin_search_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Search.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{search}', name: 'admin_search_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('search')) {
            $items[$this->coreLocator->translator()->trans('Moteurs de recherche', [], 'admin_breadcrumb')] = 'admin_search_index';
        }

        parent::breadcrumb($request, $items);
    }
}
