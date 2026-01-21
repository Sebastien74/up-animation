<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Newscast;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Newscast\Listing;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Newscast\ListingType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ListingController.
 *
 * Newscast Listing Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NEWSCAST')]
#[Route('/admin-%security_token%/{website}/newscasts/listings', schemes: '%protocol%')]
class ListingController extends AdminController
{
    protected ?string $class = Listing::class;
    protected ?string $formType = ListingType::class;

    /**
     * ListingController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->newscastListing();
        $this->exportService = $adminLocator->exportManagers()->productsService();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_newscastlisting_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_newscastlisting_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{newscastlisting}', name: 'admin_newscastlisting_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{newscastlisting}', name: 'admin_newscastlisting_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{newscastlisting}', name: 'admin_newscastlisting_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Listing.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{newscastlisting}', name: 'admin_newscastlisting_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('newscastlisting')) {
            $items[$this->coreLocator->translator()->trans('Index', [], 'admin_breadcrumb')] = 'admin_newscastlisting_index';
        }

        parent::breadcrumb($request, $items);
    }
}
