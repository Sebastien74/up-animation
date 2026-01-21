<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\User;
use App\Form\Interface\SecurityFormManagerInterface;
use App\Form\Type\Security\Admin\UserPasswordType;
use App\Form\Type\Security\Admin\UserType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * UserController.
 *
 * Security User management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_USERS')]
#[Route('/admin-%security_token%/{website}/security/users', schemes: '%protocol%')]
class UserController extends AdminController
{
    protected ?string $class = User::class;
    protected ?string $formType = UserType::class;

    /**
     * UserController constructor.
     */
    public function __construct(
        protected SecurityFormManagerInterface $securityFormLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $securityFormLocator->adminUser();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index User.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_user_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New User.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_user_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit User.
     *
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    #[Route('/edit/{user}', name: 'admin_user_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->isAllowed($request);
        $this->template = 'admin/page/security/user.html.twig';

        return parent::edit($request);
    }

    /**
     * Edit User password.
     *
     * @throws InvalidArgumentException
     */
    #[Route('/password/{user}', name: 'admin_user_password', methods: 'GET|POST')]
    public function password(Request $request)
    {
        $this->isAllowed($request);
        $this->template = 'admin/page/security/password.html.twig';
        $this->formType = UserPasswordType::class;

        return parent::edit($request);
    }

    /**
     * Show User.
     *
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    #[Route('/show/{user}', name: 'admin_user_show', methods: 'GET')]
    public function show(Request $request)
    {
        $this->isAllowed($request);

        return parent::show($request);
    }

    /**
     * Delete User.
     *
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    #[Route('/delete/{user}', name: 'admin_user_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $this->isAllowed($request);

        return parent::delete($request);
    }

    /**
     * Export User[].
     *
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    #[Route('/export/{user}', name: 'admin_user_export', methods: 'GET')]
    public function export(Request $request)
    {
        $this->isAllowed($request);

        return parent::export($request);
    }

    /**
     * Check if current User is allowed to edit internal entities.
     *
     * @throws InvalidArgumentException
     */
    private function isAllowed(Request $request): void
    {
        /** @var User $user */
        $user = $this->coreLocator->em()->getRepository(User::class)->find($request->get('user'));
        $isInternalUser = false;
        foreach ($user->getRoles() as $role) {
            if ('ROLE_INTERNAL' === $role) {
                $isInternalUser = true;
                break;
            }
        }
        if ($isInternalUser) {
            $this->denyAccessUnlessGranted('ROLE_INTERNAL');
        }
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('user')) {
            $items[$this->coreLocator->translator()->trans('Utilisateurs', [], 'admin_breadcrumb')] = 'admin_user_index';
        }

        parent::breadcrumb($request, $items);
    }
}
