<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Catalog\FeatureType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FeatureController.
 *
 * Catalog Feature management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/features', schemes: '%protocol%')]
class FeatureController extends AdminController
{
    protected ?string $class = Feature::class;
    protected ?string $formType = FeatureType::class;

    /**
     * FeatureValueController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->catalogFeature();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index Feature.
     *
     * {@inheritdoc}
     */
    #[Route('/index', name: 'admin_catalogfeature_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $unusedValues = $this->coreLocator->em()->getRepository(FeatureValueProduct::class)->findBy(['product' => null]);
        $flush = false;

        foreach ($unusedValues as $value) {
            $this->coreLocator->em()->remove($value);
            $flush = true;
        }

        if ($flush) {
            $this->coreLocator->em()->flush();
        }

        return parent::index($request, $paginator);
    }

    /**
     * New Feature.
     *
     * {@inheritdoc}
     */
    #[Route('/new', name: 'admin_catalogfeature_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit Feature.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{catalogfeature}', name: 'admin_catalogfeature_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show Feature.
     *
     * {@inheritdoc}
     */
    #[Route('/show/{catalogfeature}', name: 'admin_catalogfeature_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position Feature.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{catalogfeature}', name: 'admin_catalogfeature_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete Feature.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalogfeature}', name: 'admin_catalogfeature_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $feature = $this->coreLocator->em()->getRepository(Feature::class)->find($request->get('catalogfeature'));
        if ($feature->getValues()->count() > 0) {
            $session = new Session();
            $message = $this->coreLocator->translator()->trans('Vous ne pouvez pas supprimer cette Caractéristique car des valeurs lui sont associées.', [], 'admin');
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
        if ($request->get('catalogfeature')) {
            $items[$this->coreLocator->translator()->trans('Caractéristiques', [], 'admin_breadcrumb')] = 'admin_catalogfeature_index';
        }

        parent::breadcrumb($request, $items);
    }
}
