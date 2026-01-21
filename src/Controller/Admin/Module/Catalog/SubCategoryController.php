<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\SubCategory;
use App\Form\Type\Module\Catalog\SubCategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SubCategoryController.
 *
 * Catalog FeatureValue management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/categories/sub-categories', schemes: '%protocol%')]
class SubCategoryController extends AdminController
{
    protected ?string $class = SubCategory::class;
    protected ?string $formType = SubCategoryType::class;

    /**
     * Index FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogcategory}/index', name: 'admin_catalogsubcategory_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        return parent::index($request, $paginator);
    }

    /**
     * New FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogcategory}/new', name: 'admin_catalogsubcategory_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogcategory}/edit/{catalogsubcategory}', name: 'admin_catalogsubcategory_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogcategory}/show/{catalogsubcategory}', name: 'admin_catalogsubcategory_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{catalogsubcategory}', name: 'admin_catalogsubcategory_position', methods: 'GET')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalogsubcategory}', name: 'admin_catalogsubcategory_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $subCategory = $this->coreLocator->em()->getRepository(SubCategory::class)->find($request->get('catalogsubcategory'));
        $products = $subCategory ? $this->coreLocator->em()->getRepository(Product::class)->findBySubCategory($subCategory) : [];

        if ($products) {
            $session = new Session();
            if (1 === count($products)) {
                $message = $this->coreLocator->translator()->trans('Cette sous-catégorie est utilisée dans le produit', [], 'admin').' <strong>'.$products[0]->getAdminName().'</strong>';
            } else {
                $message = $this->coreLocator->translator()->trans('Cette sous-catégorie est utilisée dans les produits suivants :', [], 'admin');
                $message .= '<ul>';
                foreach ($products as $product) {
                    $message .= '<li><strong>'.$product->getAdminName().'</strong></li>';
                }
                $message .= '</ul>';
            }
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
            if ($request->get('catalogsubcategory')) {
                $items[$this->coreLocator->translator()->trans('Sous-catégorie', [], 'admin_breadcrumb')] = 'admin_catalogsubcategory_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
