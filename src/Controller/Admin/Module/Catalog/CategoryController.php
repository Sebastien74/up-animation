<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\Product;
use App\Form\Type\Module\Catalog\CategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CategoryController.
 *
 * Catalog Category management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/categories', schemes: '%protocol%')]
class CategoryController extends AdminController
{
    protected ?string $class = Category::class;
    protected ?string $formType = CategoryType::class;

    /**
     * Index Category.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_catalogcategory_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New Category.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_catalogcategory_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Category.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{catalogcategory}', name: 'admin_catalogcategory_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Category.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{catalogcategory}', name: 'admin_catalogcategory_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Category.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{catalogcategory}', name: 'admin_catalogcategory_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Category.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalogcategory}', name: 'admin_catalogcategory_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $category = $this->coreLocator->em()->getRepository(Category::class)->find($request->get('catalogcategory'));
        $products = $category ? $this->coreLocator->em()->getRepository(Product::class)->findByCategory($category) : [];

        if ($products) {
            $session = new Session();
            if (1 === count($products)) {
                $message = $this->coreLocator->translator()->trans('Cette catégorie est utilisée dans le produit', [], 'admin').' <strong>'.$products[0]->getAdminName().'</strong>';
            } else {
                $message = $this->coreLocator->translator()->trans('Cette catégorie est utilisée dans les produits suivants :', [], 'admin');
                $message .= '<ul>';
                foreach ($products as $product) {
                    $message .= '<li><strong>'.$product->getAdminName().'</strong></li>';
                }
                $message .= '</ul>';
            }
            $session->getFlashBag()->add('error', $message);

            return new JsonResponse(['success' => true]);
        }

        if ($category->getSubCategories()->count() > 0) {
            $session = new Session();
            $message = $this->coreLocator->translator()->trans('Vous ne pouvez pas supprimer cette catégorie car des sous-catégories lui sont associées.', [], 'admin');
            $session->getFlashBag()->add('error', $message);
            return new JsonResponse(['success' => true]);
        }

        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('catalogcategory')) {
            $items[$this->coreLocator->translator()->trans('Catégories', [], 'admin_breadcrumb')] = 'admin_catalogcategory_index';
        }

        parent::breadcrumb($request, $items);
    }
}
