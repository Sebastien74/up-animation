<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Menu;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\Menu;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Menu\LinkType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LinkController.
 *
 * Link Menu Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_NAVIGATION')]
#[Route('/admin-%security_token%/{website}/menus/links', schemes: '%protocol%')]
class LinkController extends AdminController
{
    protected ?string $class = Link::class;
    protected ?string $formType = LinkType::class;

    /**
     * LinkController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/add/{menu}/{locale}/{multiple}', name: 'admin_link_add', methods: 'GET|POST')]
    public function add(Request $request, Menu $menu, string $locale, bool $multiple): RedirectResponse
    {
        $post = filter_input_array(INPUT_POST);
        if ($post) {
            $this->moduleLocator->addLinkToMenu()->post($post, $menu, $locale, $multiple);
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * New Link.
     *
     * {@inheritdoc}
     */
    #[Route('/new/{menu}/{entitylocale}', name: 'admin_link_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        $this->template = 'admin/page/menu/new-link.html.twig';
        $this->arguments['menu'] = $request->get('menu');

        return parent::new($request);
    }

    /**
     * Edit Link.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{menu}/{link}/{entitylocale}', name: 'admin_link_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $link = $this->coreLocator->em()->getRepository(Link::class)->find($request->get('link'));
        if (!$link) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Ce lien n'existe pas !!", [], 'front'));
        }
        if ($link->getLocale() !== $request->get('entitylocale')) {
            throw $this->createNotFoundException();
        }

        return parent::edit($request);
    }

    /**
     * Delete Link.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{link}', name: 'admin_link_delete', methods: 'DELETE')]
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
            if ($request->get('link')) {
                $items[$this->coreLocator->translator()->trans('Menu', [], 'admin_breadcrumb')] = 'admin_menu_edit';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
