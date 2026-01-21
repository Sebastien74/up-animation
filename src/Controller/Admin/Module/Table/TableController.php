<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Table;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Table\Table;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Table\TableType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TableController.
 *
 * Table Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_TABLE')]
#[Route('/admin-%security_token%/{website}/tables', schemes: '%protocol%')]
class TableController extends AdminController
{
    protected ?string $class = Table::class;
    protected ?string $formType = TableType::class;

    /**
     * TableController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->table();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Table.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_table_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Table.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_table_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Table.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{table}/{entitylocale}', name: 'admin_table_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->template = 'admin/page/table/edit.html.twig';

        return parent::edit($request);
    }

    /**
     * Show Table.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{table}/{entitylocale}', name: 'admin_table_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Table.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{table}', name: 'admin_table_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Table.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{table}', name: 'admin_table_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('table')) {
            $items[$this->coreLocator->translator()->trans('Tableaux', [], 'admin_breadcrumb')] = 'admin_table_index';
        }

        parent::breadcrumb($request, $items);
    }
}
