<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Recruitment;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Recruitment\Category;
use App\Form\Type\Module\Recruitment\CategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;;

/**
 * CategoryController
 *
 * Newscast Category Action management
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
#[IsGranted('ROLE_RECRUITMENT')]
#[Route('/admin-%security_token%/{website}/recruitments/categories', schemes: '%protocol%')]
class CategoryController extends AdminController
{
    protected ?string $class = Category::class;
    protected ?string $formType = CategoryType::class;

    /**
     * Index Category
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_recruitmentcategory_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Category
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_recruitmentcategory_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Category
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{recruitmentcategory}', name: 'admin_recruitmentcategory_layout', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Category
     *
     * {@inheritdoc}
     */
    #[Route('/show/{recruitmentcategory}', name: 'admin_recruitmentcategory_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Category
     *
     * {@inheritdoc}
     */
    #[Route('/position/{recruitmentcategory}', name: 'admin_recruitmentcategory_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Category
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{recruitmentcategory}', name: 'admin_recruitmentcategory_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}