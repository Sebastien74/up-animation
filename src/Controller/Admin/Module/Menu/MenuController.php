<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Menu;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\Menu;
use App\Form\Type\Module\Menu\MenuType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MenuController.
 *
 * Menu Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NAVIGATION')]
#[Route('/admin-%security_token%/{website}/menus', schemes: '%protocol%')]
class MenuController extends AdminController
{
    protected ?string $class = Menu::class;
    protected ?string $formType = MenuType::class;

    /**
     * Index Menu.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_menu_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Menu.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_menu_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Menu.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{menu}/{entitylocale}', name: 'admin_menu_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->checkEntityLocale($request);
        $this->template = 'admin/page/menu/edit.html.twig';
        $menu = $this->coreLocator->em()->getRepository(Menu::class)->findArray($request->attributes->getInt('menu'));
        if (!$menu) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Ce menu n'existe pas !!", [], 'front'));
        }

        $website = $this->coreLocator->em()->getRepository(Website::class)->findObject($request->attributes->getInt('website'));

        $this->arguments['pages'] = $this->adminLocator->treeHelper()->execute(Page::class, $this->getInterface(Page::class), $website);
        $this->arguments['treePages'] = $this->getTree($this->arguments['pages']);

        $links = $this->coreLocator->em()->getRepository(Link::class)->findByMenuAndLocale($menu, $request->get('entitylocale'));
        $this->arguments['tree'] = $this->getTree($links);

        $formPositions = $this->getTreeForm($request, Link::class);
        if ($formPositions instanceof JsonResponse) {
            return $formPositions;
        }

        $this->arguments['formPositions'] = $formPositions->createView();
        $arguments = $this->editionArguments($request);

        return $this->forward('App\Controller\Admin\AdminController::edition', $arguments);
    }

    /**
     * Show Menu.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{menu}/{entitylocale}', name: 'admin_menu_show', methods: 'GET')]
    public function show(Request $request)
    {
        $this->checkEntityLocale($request);
        return parent::show($request);
    }

    /**
     * Position Menu.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{menu}', name: 'admin_menu_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Menu.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{menu}', name: 'admin_menu_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('menu')) {
            $items[$this->coreLocator->translator()->trans('Navigations', [], 'admin_breadcrumb')] = 'admin_menu_index';
        }

        parent::breadcrumb($request, $items);
    }
}
