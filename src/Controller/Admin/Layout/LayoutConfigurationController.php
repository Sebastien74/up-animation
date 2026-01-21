<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\LayoutConfiguration;
use App\Form\Interface\LayoutFormFormManagerLocator;
use App\Form\Type\Layout\Management\LayoutConfigurationType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * LayoutController.
 *
 * Layout management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/layouts/configurations', schemes: '%protocol%')]
class LayoutConfigurationController extends AdminController
{
    protected ?string $class = LayoutConfiguration::class;
    protected ?string $formType = LayoutConfigurationType::class;

    /**
     * LayoutConfigurationController constructor.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function __construct(
        protected LayoutFormFormManagerLocator $layoutLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $layoutLocator->layoutConfiguration();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index LayoutConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_layoutconfiguration_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New LayoutConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_layoutconfiguration_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit LayoutConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{layoutconfiguration}', name: 'admin_layoutconfiguration_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Delete LayoutConfiguration.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{layoutconfiguration}', name: 'admin_layoutconfiguration_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('layoutconfiguration')) {
            $items[$this->coreLocator->translator()->trans('Configurations', [], 'admin_breadcrumb')] = 'admin_layoutconfiguration_index';
        }

        parent::breadcrumb($request, $items);
    }
}
