<?php

declare(strict_types=1);

namespace App\Controller\Admin\Module\Catalog;

use App\Controller\Admin\AdminController;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Product;
use App\Form\Interface\ModuleFormManagerInterface;
use App\Model\Module\ProductModel;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * FeatureValueProductController.
 *
 * FeatureValueProduct management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_CATALOG')]
#[Route('/admin-%security_token%/{website}/module/catalogs/catalogfeaturevalueproduct', schemes: '%protocol%')]
class FeatureValueProductController extends AdminController
{
    protected ?string $class = FeatureValueProduct::class;

    /**
     * ProductController constructor.
     */
    public function __construct(
        protected ModuleFormManagerInterface $moduleFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Delete FeatureValueProduct.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{catalogfeaturevalueproduct}', name: 'admin_catalogfeaturevalueproduct_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $value = $this->coreLocator->em()->getRepository(FeatureValueProduct::class)->find($request->get('catalogfeaturevalueproduct'));
        $product = $value->getProduct();
        $this->arguments['redirection'] = $this->generateUrl('admin_catalogproduct_edit', [
            'website' => $this->coreLocator->website()->id,
            'catalogproduct' => $product->getId(),
            'catalog' => $product->getCatalog()->getId(),
            'tab' => 'features',
        ]);

        $catalogId = $product->getCatalog()->getId();
        $asDefault = $this->asDefault($value, $catalogId, 'feature');
        $asDefault = $asDefault || $this->asDefault($value, $catalogId, 'value');

        if ($asDefault) {
            $session = new Session();
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans("Vous ne pouvez pas supprimer une caractéristique par défault.<br> Vous devez d'abord retirer le catalogue dans la configuration de votre caractéristique ou valeur.", [], 'admin'));
            return new JsonResponse(['success' => true, 'redirection' => $this->arguments['redirection']]);
        } else {
            $this->formManager = $this->moduleFormInterface->catalogProduct();
            $this->arguments['entity'] = $product;
            $this->arguments['formManagerMethod'] = 'setValues';
        }

        return parent::delete($request);
    }

    /**
     * To check if is default Feature or Value.
     */
    private function asDefault(FeatureValueProduct $value, int $catalogId, string $property): bool
    {
        $method = 'get'.ucfirst($property);
        if ($value->$method()) {
            foreach ($value->$method()->getCatalogs() as $catalog) {
                if ($catalog->getId() == $catalogId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Position FeatureValueProduct.
     */
    #[Route('/position/{catalogfeaturevalueproduct}', name: 'admin_catalogfeaturevalueproduct_position', methods: 'GET|POST')]
    public function valuePosition(Request $request): JsonResponse
    {
        $value = $this->coreLocator->em()->getRepository($this->class)->find($request->attributes->getInt('catalogfeaturevalueproduct'));
        $product = $value ? $value->getProduct() : null;
        $this->adminLocator->positionService()->setByJsonArray($request->get('data'), $this->class);
        if ($product) {
            $this->moduleFormInterface->catalogProduct()->setValues($product, $this->coreLocator->website()->entity);
        }
        return new JsonResponse(['success' => true]);
    }

    /**
     * Position FeatureValueProduct.
     */
    #[Route('/feature-position/{product}/{feature}', name: 'admin_catalogfeaturevalueproduct_feature_position', methods: 'GET|POST')]
    public function featurePosition(Request $request, Product $product, Feature $feature): JsonResponse
    {
        foreach ($product->getValues() as $value) {
            if ($value->getFeature()->getId() === $feature->getId()) {
                $value->setPosition($request->get('position'));
                $value->setFeaturePosition($request->get('position'));
                $this->coreLocator->em()->persist($value);
            }
        }
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }
}
