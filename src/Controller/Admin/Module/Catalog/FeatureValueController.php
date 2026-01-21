<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Form\Type\Module\Catalog\FeatureValueType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FeatureValueController.
 *
 * Catalog FeatureValue management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/features/values', schemes: '%protocol%')]
class FeatureValueController extends AdminController
{
    protected ?string $class = FeatureValue::class;
    protected ?string $formType = FeatureValueType::class;

    /**
     * FeatureValueController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $moduleFormInterface->catalogFeatureValue();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Index FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogfeature}/index', name: 'admin_catalogfeaturevalue_index', methods: 'GET|POST')]
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $feature = $this->coreLocator->em()->getRepository(Feature::class)->find($request->attributes->getInt('catalogfeature'));
        $prefix = $this->coreLocator->translator()->trans('Valeurs de caractéristique', [], 'admin');
        $this->pageTitle = $feature ? $prefix.' : '.$feature->getAdminName() : $prefix;

        return parent::index($request, $paginator);
    }

    /**
     * New FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogfeature}/new', name: 'admin_catalogfeaturevalue_new', methods: 'GET|POST')]
    public function new(Request $request)
    {
        return parent::new($request);
    }

    /**
     * Edit FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogfeature}/edit/{catalogfeaturevalue}', name: 'admin_catalogfeaturevalue_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Show FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/{catalogfeature}/show/{catalogfeaturevalue}', name: 'admin_catalogfeaturevalue_show', methods: 'GET')]
    public function show(Request $request)
    {
        return parent::show($request);
    }

    /**
     * Position FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/position/{catalogfeaturevalue}', name: 'admin_catalogfeaturevalue_position', methods: 'GET|POST')]
    public function position(Request $request)
    {
        return parent::position($request);
    }

    /**
     * Delete FeatureValue.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalogfeaturevalue}', name: 'admin_catalogfeaturevalue_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $values = $this->coreLocator->em()->getRepository(FeatureValueProduct::class)->findBy(['value' => $request->get('catalogfeaturevalue')]);
        if ($values) {
            $session = new Session();
            if (1 === count($values)) {
                $message = $this->coreLocator->translator()->trans('Cette valeur est utilisée dans le produit', [], 'admin').' <strong>'.$values[0]->getProduct()->getAdminName().'</strong>';
            } else {
                $message = $this->coreLocator->translator()->trans('Cette valeur est utilisée dans les produits suivants :', [], 'admin');
                $message .= '<ul>';
                foreach ($values as $value) {
                    $message .= '<li><strong>'.$value->getProduct()->getAdminName().'</strong></li>';
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
        if ($request->get('catalogfeature')) {
            $items[$this->coreLocator->translator()->trans('Caractéristiques', [], 'admin_breadcrumb')] = 'admin_catalogfeature_index';
            if ($request->get('catalogfeaturevalue')) {
                $items[$this->coreLocator->translator()->trans('Valeurs', [], 'admin_breadcrumb')] = 'admin_catalogfeaturevalue_index';
            }
        }

        parent::breadcrumb($request, $items);
    }
}
