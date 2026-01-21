<?php

declare(strict_types=1);

namespace App\Controller\Security\Front;

use App\Controller\Admin\AdminController;
use App\Entity\Security\UserFront;
use App\Form\Interface\SecurityFormManagerInterface;
use App\Form\Manager\Security\Front\RegisterManager;
use App\Form\Type\Security\Front\UserPasswordType;
use App\Form\Type\Security\Front\UserType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
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
#[Route('/admin-%security_token%/{website}/security/users-front', schemes: '%protocol%')]
class UserController extends AdminController
{
    protected ?string $class = UserFront::class;
    protected ?string $formType = UserType::class;

    /**
     * UserController constructor.
     */
    public function __construct(
        protected SecurityFormManagerInterface $securityFormLocator,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
        protected RegisterManager $registerManager,
    ) {
        $this->formManager = $securityFormLocator->frontUser();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index UserFront.
     *
     * {@inheritdoc}
     */
    #[Route('/index/{role}', name: 'admin_userfront_index', defaults: ['role' => null], methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $role = $request->get('role') ? 'ROLE_'.strtoupper($request->get('role')) : null;
        if ($role) {
            $this->forceEntities = true;
            $this->entities = $this->coreLocator->em()->getRepository($this->class)->findByWebsiteAndRole($this->getWebsite()->entity, $role);
        }
        $this->registerManager->removeExpiredToken();

        return parent::index($request, $paginator);
    }

    /**
     * New UserFront.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_userfront_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit UserFront.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{userfront}', name: 'admin_userfront_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->template = 'admin/page/security/user-front.html.twig';

        return parent::edit($request);
    }

    /**
     * Edit UserFront password.
     */
    #[Route('/password/{userfront}', name: 'admin_userfront_password', methods: 'GET|POST')]
    public function password(Request $request)
    {
        $this->entity = $this->coreLocator->em()->getRepository($this->class)->find($request->get('userfront'));
        $this->template = 'admin/page/security/password-user-front.html.twig';
        $this->formType = UserPasswordType::class;

        return parent::edit($request);
    }

    /**
     * Show UserFront.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{userfront}', name: 'admin_userfront_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Delete UserFront.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{userfront}', name: 'admin_userfront_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * Export UserFront[].
     *
     * {@inheritdoc}
     */
    #[Route('/export', name: 'admin_userfront_export', methods: 'GET')]
    public function export(Request $request)
    {
        return parent::export($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('userfront')) {
            $items[$this->coreLocator->translator()->trans('Utilisateurs', [], 'admin_breadcrumb')] = 'admin_userfront_index';
        }

        parent::breadcrumb($request, $items);
    }
}
