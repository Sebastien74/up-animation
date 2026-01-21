<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * CatalogFeatureManager.
 *
 * Manage Feature in admin.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CatalogFeatureManager::class, 'key' => 'module_catalog_feature_form_manager'],
])]
class CatalogFeatureManager
{
    /**
     * CatalogFeatureValueManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CatalogProductManager $productManager,
    )
    {
    }

    /**
     * @prePersist
     */
    public function prePersist(Feature $feature, Website $website, array $interface, Form $form): void
    {
        $this->coreLocator->em()->persist($feature);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(Feature $feature, Website $website, array $interface, Form $form): void
    {
        $post = $this->coreLocator->request()->get('feature');
        if (isset($post['removeCards'])) {
            $valueProducts = $this->coreLocator->entityManager()->getRepository(FeatureValueProduct::class)->findBy(['feature' => $feature, 'asDefault' => true]);
            foreach ($valueProducts as $value) {
                $product = $value->getProduct();
                if ($product) {
                    $productValues = $this->coreLocator->entityManager()->getRepository(FeatureValueProduct::class)->findBy(['product' => $product], ['position' => 'ASC']);
                    foreach ($productValues as $productValue) {
                        if ($productValue->getFeature() && $productValue->getFeature()->getId() === $feature->getId()) {
                            $product->removeValue($productValue);
                            $this->coreLocator->em()->remove($productValue);
                        }
                    }
                    $this->productManager->setValuesPositions($product);
                    $this->productManager->setJsonValues($product);
                    $this->coreLocator->em()->persist($product);
                }
            }
        }
    }
}
