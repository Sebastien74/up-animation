<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Catalog;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Catalog\CatalogType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CatalogController.
 *
 * Catalog management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/catalogs', schemes: '%protocol%')]
class CatalogController extends AdminController
{
    protected ?string $class = Catalog::class;
    protected ?string $formType = CatalogType::class;

    /**
     * CatalogController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_catalog_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_catalog_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/layout/{catalog}', name: 'admin_catalog_layout', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{catalog}', name: 'admin_catalog_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{catalog}', name: 'admin_catalog_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Catalog.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalog}', name: 'admin_catalog_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('catalog')) {
            $items[$this->coreLocator->translator()->trans('Catalogues', [], 'admin_breadcrumb')] = 'admin_catalog_index';
        }

        parent::breadcrumb($request, $items);
    }
}
