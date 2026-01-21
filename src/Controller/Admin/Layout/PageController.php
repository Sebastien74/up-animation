<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Page;
use App\Form\Interface\LayoutFormFormManagerLocator;
use App\Form\Type\Layout\PageDuplicateType;
use App\Form\Type\Layout\PageType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * PageController.
 *
 * Page management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_PAGE')]
#[Route('/admin-%security_token%/{website}/pages', schemes: '%protocol%')]
class PageController extends AdminController
{
    protected ?string $class = Page::class;
    protected ?string $formType = PageType::class;

    /**
     * PageController constructor.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function __construct(
        protected LayoutFormFormManagerLocator $layoutLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $layoutLocator->page();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Pages tree.
     *
     * {@inheritdoc}
     */
    #[Route('/tree', name: 'admin_page_tree', methods: 'GET|POST')]
    public function tree(Request $request)
    {
        return parent::tree($request);
    }

    /**
     * New Page.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_page_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Layout Page.
     *
     * {@inheritdoc}
     */
    #[Route('/layout/{page}', name: 'admin_page_layout', methods: 'GET|POST')]
    public function layout(Request $request)
    {
        $this->templateConfig = 'admin/page/content/page-configuration.html.twig';

        return parent::layout($request);
    }

    /**
     * Duplicate Page.
     */
    #[Route('/duplicate/{page}', name: 'admin_page_duplicate', methods: 'GET|POST')]
    public function duplicate(Request $request)
    {
        $this->formType = PageDuplicateType::class;
        $this->formDuplicateManager = $this->layoutLocator->pageDuplicate();

        return parent::duplicate($request);
    }

    /**
     * Delete Page.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{page}', name: 'admin_page_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('page')) {
            $items[$this->coreLocator->translator()->trans('Arborescence', [], 'admin_breadcrumb')] = 'admin_page_tree';
        }

        parent::breadcrumb($request, $items);
    }
}
