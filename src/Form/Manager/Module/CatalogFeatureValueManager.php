<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Product;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * CatalogFeatureValueManager.
 *
 * Manage FeatureValue in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CatalogFeatureValueManager::class, 'key' => 'module_catalog_feature_value_form_manager'],
])]
class CatalogFeatureValueManager
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
    public function prePersist(FeatureValue $featureValue, Website $website, array $interface, Form $form): void
    {
        $this->coreLocator->em()->persist($featureValue);
    }

    /**
     * @preUpdate
     */
    public function preUpdate(FeatureValue $featureValue, Website $website, array $interface, Form $form): void
    {
        $post = $this->coreLocator->request()->get('feature_value');
        $featureBeforePostId = intval($post['featureBeforePost']);
        $featureBeforePost = $this->coreLocator->em()->getRepository(Feature::class)->find($featureBeforePostId);
        $currentFeature = $featureValue->getCatalogfeature();

        if ($currentFeature->getId() !== $featureBeforePost->getId()) {
            $this->setProductsFeature($featureValue);
            $this->setPositions($featureValue, $currentFeature);
        }

        $this->coreLocator->em()->persist($featureValue);

        if (isset($post['removeCards'])) {
            $valueProducts = $this->coreLocator->entityManager()->getRepository(FeatureValueProduct::class)->findBy(['value' => $featureValue->getId(), 'asDefault' => true]);
            foreach ($valueProducts as $value) {
                $product = $value->getProduct();
                if ($product) {
                    $productValues = $this->coreLocator->entityManager()->getRepository(FeatureValueProduct::class)->findBy(['product' => $product], ['position' => 'ASC']);
                    foreach ($productValues as $productValue) {
                        if ($productValue->getValue() && $productValue->getValue()->getId() === $featureValue->getId()) {
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

    /**
     * Set FeatureValueProduct Product[].
     */
    private function setProductsFeature(FeatureValue $featureValue): void
    {
        $products = $this->coreLocator->em()->getRepository(Product::class)->findByValue($featureValue);
        foreach ($products as $product) {
            /* @var Product $product */
            foreach ($product->getValues() as $value) {
                if ($value->getValue()->getId() === $featureValue->getId()) {
                    $value->setFeature($featureValue->getCatalogfeature());
                    $this->coreLocator->em()->persist($value);
                }
            }
        }
    }

    /**
     * Set FeatureValue positions.
     */
    private function setPositions(FeatureValue $featureValue, Feature $currentFeature): void
    {
        foreach ($featureValue->getCatalogfeature()->getValues() as $value) {
            if ($value->getPosition() > $featureValue->getPosition()) {
                $value->setPosition($value->getPosition() - 1);
                $this->coreLocator->em()->persist($value);
            }
        }
        $featureValue->setPosition($currentFeature->getValues()->count() + 1);
    }
}
