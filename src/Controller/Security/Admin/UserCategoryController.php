<?php

declare(strict_types=1);

namespace App\Controller\Security\Admin;

use App\Controller\Admin\AdminController;
use App\Entity\Security\UserCategory;
use App\Form\Type\Security\Admin\UserCategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * UserCategoryController.
 *
 * Security UserCategory management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/security/users/categories', schemes: '%protocol%')]
class UserCategoryController extends AdminController
{
    protected ?string $class = UserCategory::class;
    protected ?string $formType = UserCategoryType::class;

    /**
     * Index UserCategory.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_securityusercategory_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New UserCategory.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_securityusercategory_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit UserCategory.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{securityusercategory}', name: 'admin_securityusercategory_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show UserCategory.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{securityusercategory}', name: 'admin_securityusercategory_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position UserCategory.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{securityusercategory}', name: 'admin_securityusercategory_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete UserCategory.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{securityusercategory}', name: 'admin_securityusercategory_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('securityusercategory')) {
            $items[$this->coreLocator->translator()->trans('Catégories', [], 'admin_breadcrumb')] = 'admin_securityusercategory_index';
        }

        parent::breadcrumb($request, $items);
    }
}
