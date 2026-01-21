<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Product;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Catalog\ProductType;
use App\Form\Type\Translation\ImportType;
use App\Model\Module\ProductModel;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ProductController.
 *
 * Catalog Product management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/products', schemes: '%protocol%')]
class ProductController extends AdminController
{
    protected ?string $class = Product::class;
    protected ?string $formType = ProductType::class;

    /**
     * ProductController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->catalogProduct();
        $this->exportService = $adminLocator->exportManagers()->productsService();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Product.
     *
     * {@inheritdoc}
     */
    #[Route('/index/{catalog}', name: 'admin_catalogproduct_index', defaults: ['catalog' => null], methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        if ($request->get('catalog')) {
            $catalog = $this->coreLocator->em()->getRepository(Catalog::class)->find($request->attributes->getInt('catalog'));
            $this->pageTitle = $catalog ? $catalog->getAdminName() : null;
        } else {
            $this->arguments['interfaceOrderBy'] = 'adminName';
            $this->arguments['interfaceOrderSort'] = 'ASC';
            $this->arguments['interfaceHideColumns'] = ['position'];
        }

        return parent::index($request, $paginator);
    }

    /**
     * New Product.
     *
     * {@inheritdoc}
     */
    #[Route('/new/{catalog}', name: 'admin_catalogproduct_new', defaults: ['catalog' => null], methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Product.
     *
     * @throws MappingException|NonUniqueResultException|QueryException
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{catalogproduct}/{catalog}/{tab}', name: 'admin_catalogproduct_edit', defaults: ['catalog' => null, 'tab' => null], methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->entity = $this->coreLocator->em()->getRepository(Product::class)->findForAdmin($request->attributes->getInt('catalogproduct'));
        if (!$this->entity) {
            throw new NotFoundHttpException();
        }
        if (!$request->isMethod('post')) {
            ProductModel::fromEntity($this->entity, $this->coreLocator);
        }
        $this->template = 'admin/page/catalog/product-edit.html.twig';
        $this->formOptions['isDraggable'] = $this->formManager->isDraggable();
        $this->arguments['activeTab'] = $request->get('tab');
        $this->arguments['activeTabs'] = $this->formOptions['activesFields'] = $this->entity->getCatalog()->getTabs();

        return parent::edit($request);
    }

    /**
     * Medias Product.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    #[Route('/medias/{catalogproduct}/{catalog}', name: 'admin_catalogproduct_medias', defaults: ['catalog' => null], methods: 'GET|POST')]
    public function medias(Request $request): Response
    {
        $product = $this->coreLocator->em()->getRepository(Product::class)->find($request->get('catalogproduct'));
        if (!$product) {
            throw new NotFoundHttpException();
        }
        $this->arguments['activeTabs'] = $this->formOptions['activesFields'] = $product->getCatalog()->getTabs();
        $this->breadcrumb($request);

        return $this->render('admin/page/catalog/product-medias.html.twig', array_merge($this->arguments, [
            'entity' => $product,
            'website' => $this->getWebsite(),
            'activeTabs' => $this->arguments['activeTabs'],
            'interface' => $this->getInterface($this->class),
            'tooHeavyFiles' => $this->adminLocator->tooHeavyFiles($product),
            'mediasAlert' => $this->adminLocator->mediasAlert($product),
            'seoAlert' => $this->coreLocator->seoService()->seoAlert($product, $this->coreLocator->website()),
        ]));
    }

    /**
     * Video Product.
     *
     * {@inheritdoc}
     */
    #[Route('/video/{catalogproduct}', name: 'admin_catalogproduct_video', methods: 'GET|POST')]
    public function video(Request $request): RedirectResponse
    {
        return parent::video($request);
    }

    /**
     * Show Product.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{catalogproduct}/{catalog}', name: 'admin_catalogproduct_show', defaults: ['catalog' => null], methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Import Product[].
     *
     * {@inheritdoc}
     */
    #[Route('/import', name: 'admin_catalogproduct_import', methods: 'GET|POST')]
    public function import(Request $request)
    {
        $form = $this->createForm(ImportType::class);
        $form->handleRequest($request);
        $arguments['form'] = $form->createView();
        if ($form->isSubmitted() && !empty($form->getData()['files'])) {
            $this->adminLocator->importManagers()->productsService()->execute($form, $this->getWebsite()->entity);
            return $this->redirectToRoute('admin_catalogproduct_index', ['website' => $this->getWebsite()->entity->getId(), 'catalog' => $request->query->get('catalog')]);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return new JsonResponse(['html' => $this->renderView('admin/page/catalog/import.html.twig', $arguments)]);
        }
        return $this->adminRender('admin/page/catalog/import.html.twig', $arguments);
    }

    /**
     * Export Product[].
     *
     * {@inheritdoc}
     */
    #[Route('/export', name: 'admin_catalogproduct_export', methods: 'GET|POST')]
    public function export(Request $request)
    {
        return parent::export($request);
    }

    /**
     * Position Product.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{catalogproduct}/{catalog}', name: 'admin_catalogproduct_position', defaults: ['catalog' => null], methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Product.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalogproduct}', name: 'admin_catalogproduct_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('catalog')) {
            $items[$this->coreLocator->translator()->trans('Catalogues', [], 'admin_breadcrumb')] = 'admin_catalog_index';
            if ($request->get('catalogproduct')) {
                $catalog = $this->coreLocator->em()->getRepository(Catalog::class)->find($request->get('catalog'));
                $label = $catalog ? $catalog->getAdminName() : $this->coreLocator->translator()->trans('Produits', [], 'admin_breadcrumb');
                $items[$label] = 'admin_catalogproduct_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
