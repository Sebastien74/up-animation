<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Gallery;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Gallery\Listing;
use App\Form\Type\Module\Gallery\ListingType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ListingController.
 *
 * Gallery Listing Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_GALLERY')]
#[Route('/admin-%security_token%/{website}/galleries/listings', schemes: '%protocol%')]
class ListingController extends AdminController
{
    protected ?string $class = Listing::class;
    protected ?string $formType = ListingType::class;

    /**
     * Index Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_gallerylisting_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_gallerylisting_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{gallerylisting}', name: 'admin_gallerylisting_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{gallerylisting}', name: 'admin_gallerylisting_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{gallerylisting}', name: 'admin_gallerylisting_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{gallerylisting}', name: 'admin_gallerylisting_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('gallerylisting')) {
            $items[$this->coreLocator->translator()->trans('Index', [], 'admin_breadcrumb')] = 'admin_gallerylisting_index';
        }

        parent::breadcrumb($request, $items);
    }
}
