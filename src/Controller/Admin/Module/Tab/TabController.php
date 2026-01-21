<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Tab;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Tab\Tab;
use App\Form\Type\Module\Tab\TabType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * TabController.
 *
 * Tab Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_TAB')]
#[Route('/admin-%security_token%/{website}/tabs', schemes: '%protocol%')]
class TabController extends AdminController
{
    protected ?string $class = Tab::class;
    protected ?string $formType = TabType::class;

    /**
     * Index Tab.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_tab_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Tab.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_tab_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Tab.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{tab}', name: 'admin_tab_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Tab.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{tab}', name: 'admin_tab_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Tab.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{tab}', name: 'admin_tab_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Tab.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{tab}', name: 'admin_tab_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('tab')) {
            $items[$this->coreLocator->translator()->trans("Groupes d'onglets", [], 'admin_breadcrumb')] = 'admin_tab_index';
        }

        parent::breadcrumb($request, $items);
    }
}
