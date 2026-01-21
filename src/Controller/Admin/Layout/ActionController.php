<?php

declare(strict_types=1);

namespace App\Controller\Admin\Layout;

use App\Controller\Admin\AdminController;
use App\Entity\Layout\Action;
use App\Form\Interface\LayoutFormFormManagerLocator;
use App\Form\Type\Layout\Management\ActionType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ActionController.
 *
 * Layout Action management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/layouts/actions', schemes: '%protocol%')]
class ActionController extends AdminController
{
    protected ?string $class = Action::class;
    protected ?string $formType = ActionType::class;

    /**
     * ActionController constructor.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function __construct(
        protected LayoutFormFormManagerLocator $layoutLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $layoutLocator->action();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Action.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_action_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Action.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_action_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Action.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{action}', name: 'admin_action_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Action.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{action}', name: 'admin_action_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Action.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{action}', name: 'admin_action_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Action.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{action}', name: 'admin_action_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
