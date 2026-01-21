<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Form\Interface\SecurityFormManagerInterface;
use App\Form\Type\Security\Admin\RoleType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * RoleController.
 *
 * Security Role management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/security/roles', schemes: '%protocol%')]
class RoleController extends AdminController
{
    protected ?string $class = Role::class;
    protected ?string $formType = RoleType::class;

    /**
     * RoleController constructor.
     */
    public function __construct(
        protected SecurityFormManagerInterface $securityFormLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $securityFormLocator->adminRole();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Action.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_securityrole_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Action.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_securityrole_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Action.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{securityrole}', name: 'admin_securityrole_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Action.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{securityrole}', name: 'admin_securityrole_show', methods: 'GET|POST')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Action.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{securityrole}', name: 'admin_securityrole_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Action.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{securityrole}', name: 'admin_securityrole_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $role = $this->coreLocator->em()->getRepository(Role::class)->find($request->get('securityrole'));
        $groups = $this->coreLocator->em()->getRepository(Group::class)->findAll();
        foreach ($groups as $group) {
            foreach ($group->getRoles() as $roleGroup) {
                if ($role->getId() === $roleGroup->getId()) {
                    $group->removeRole($roleGroup);
                    $this->coreLocator->em()->persist($group);
                }
            }
        }

        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('securityrole')) {
            $items[$this->coreLocator->translator()->trans('Rôles', [], 'admin_breadcrumb')] = 'admin_securityrole_index';
        }

        parent::breadcrumb($request, $items);
    }
}
