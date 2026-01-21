<?php

declare(strict_types=1);

namespace App\Controller\Admin\Gdpr;

use App\Controller\Admin\AdminController;
use App\Entity\Gdpr\Category;
use App\Form\Type\Gdpr\CategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CategoryController.
 *
 * Gdpr Category management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_INTERNAL')]
#[Route('/admin-%security_token%/{website}/gdpr/categories', schemes: '%protocol%')]
class CategoryController extends AdminController
{
    protected ?string $class = Category::class;
    protected ?string $formType = CategoryType::class;

    /**
     * Index Gdpr Category.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_gdprcategory_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Gdpr Category.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_gdprcategory_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Gdpr Category.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{gdprcategory}', name: 'admin_gdprcategory_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Gdpr Category.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{gdprcategory}', name: 'admin_gdprcategory_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Gdpr Category.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{gdprcategory}', name: 'admin_gdprcategory_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Gdpr Category.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{gdprcategory}', name: 'admin_gdprcategory_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }
}
