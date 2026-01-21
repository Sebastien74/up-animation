<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Listing;
use App\Form\Type\Module\Catalog\ListingType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ListingController.
 *
 * Catalog Listing management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/listings', schemes: '%protocol%')]
class ListingController extends AdminController
{
    protected ?string $class = Listing::class;
    protected ?string $formType = ListingType::class;

    /**
     * Index Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_cataloglisting_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_cataloglisting_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{cataloglisting}', name: 'admin_cataloglisting_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{cataloglisting}', name: 'admin_cataloglisting_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{cataloglisting}', name: 'admin_cataloglisting_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{cataloglisting}', name: 'admin_cataloglisting_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('cataloglisting')) {
            $items[$this->coreLocator->translator()->trans('Index', [], 'admin_breadcrumb')] = 'admin_cataloglisting_index';
        }

        parent::breadcrumb($request, $items);
    }
}
