<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Form\Interface\SecurityFormManagerInterface;
use App\Form\Type\Security\Admin\GroupPasswordType;
use App\Form\Type\Security\Admin\GroupType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * GroupController.
 *
 * Security Group management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_USERS_GROUP')]
#[Route('/admin-%security_token%/{website}/security/groups', schemes: '%protocol%')]
class GroupController extends AdminController
{
    protected ?string $class = Group::class;
    protected ?string $formType = GroupType::class;

    /**
     * GroupController constructor.
     */
    public function __construct(
        protected SecurityFormManagerInterface $securityFormLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Action.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_securitygroup_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Action.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_securitygroup_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        $this->entity = new Group();
        $roleRepository = $this->coreLocator->em()->getRepository(Role::class);
        $this->entity->addRole($roleRepository->findOneBy(['name' => 'ROLE_USER']));
        $this->entity->addRole($roleRepository->findOneBy(['name' => 'ROLE_ADMIN']));

        return parent::new($request);
    }

    /**
     * Edit Action.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{securitygroup}', name: 'admin_securitygroup_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->isAllowed($request);
        $this->template = 'admin/page/security/group.html.twig';
        $this->securityFormLocator->adminRole()->clearRolesCache();

        return parent::edit($request);
    }

    /**
     * Edit User password.
     */
    #[Route('/password/{securitygroup}', name: 'admin_securitygroup_password', methods: 'GET|POST')]
    public function password(Request $request)
    {
        $website = $this->getWebsite();
        if (!$website->security->isResetPasswordsByGroup()) {
            return new Response();
        }

        $this->isAllowed($request);
        $this->template = 'admin/page/security/password-group.html.twig';
        $this->formType = GroupPasswordType::class;
        $this->formManager = $this->securityFormLocator->adminGroupPassword();

        return parent::edit($request);
    }

    /**
     * Show Action.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{securitygroup}', name: 'admin_securitygroup_show', methods: 'GET')]
    public function show(Request $request)
    {
        $this->isAllowed($request);

        return parent::show($request);
    }

    /**
     * Position Action.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{securitygroup}', name: 'admin_securitygroup_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        $this->isAllowed($request);

        return parent::position($request);
    }

    /**
     * Delete Action.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{securitygroup}', name: 'admin_securitygroup_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $this->isAllowed($request);

        return parent::delete($request);
    }

    /**
     * Check if current User is allowed to edit internal entities.
     */
    private function isAllowed(Request $request): void
    {
        /** @var Group $group */
        $group = $this->coreLocator->em()->getRepository(Group::class)->find($request->get('securitygroup'));
        if ($group) {
            $isInternalGroup = false;
            foreach ($group->getRoles() as $role) {
                if ('ROLE_INTERNAL' === $role->getName()) {
                    $isInternalGroup = true;
                    break;
                }
            }
            if ($isInternalGroup) {
                $this->denyAccessUnlessGranted('ROLE_INTERNAL');
            }
        }
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('securitygroup')) {
            $items[$this->coreLocator->translator()->trans('Groupes', [], 'admin_breadcrumb')] = 'admin_securitygroup_index';
        }

        parent::breadcrumb($request, $items);
    }
}
