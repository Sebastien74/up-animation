<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Product;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CatalogRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CatalogRuntime implements RuntimeExtensionInterface
{
    private ?Request $request;

    /**
     * CatalogRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get Product FeaturesValueProduct group by Feature.
     */
    public function productData(Product $product): array
    {
        $byName = [];
        $bySlug = [];
        $byGroupPosition = [];
        $byGroupFeaturesPosition = [];
        $byPosition = [];
        $featuresById = [];
        $featuresBySlug = [];
        $featuresByPosition = [];

        foreach ($product->getValues() as $value) {
            if ($value->getFeature()) {
                $feature = $value->getFeature();
                $byName[$feature->getAdminName()][] = $value;
                $bySlug[$feature->getSlug()][] = $value;
                $byPosition[$value->getPosition()] = $value;
                $byGroupPosition[$value->getPosition()][$value->getFeaturePosition()] = $value;
                $byGroupFeaturesPosition[$value->getFeaturePosition()][$value->getFeaturePosition()] = $value;
                $featuresById[$feature->getId()] = $feature;
                $featuresBySlug[$feature->getSlug()] = $feature;
                $featuresByPosition[$value->getFeaturePosition()] = $feature;
                ksort($byName);
                ksort($bySlug);
                ksort($byPosition);
                ksort($byGroupPosition);
                ksort($byGroupPosition[$value->getPosition()]);
                ksort($featuresById);
                ksort($featuresBySlug);
                ksort($featuresByPosition);
            }
        }

        $mainCategory = $product->getMainCategory();

        return [
            'mainCategory' => $mainCategory ?: $product->getCategories()->first(),
            'byName' => $byName,
            'bySlug' => $bySlug,
            'byPosition' => $byPosition,
            'byGroupPosition' => $byGroupPosition,
            'byGroupFeaturesPosition' => $byGroupFeaturesPosition,
            'featuresById' => $featuresById,
            'featuresByPosition' => $featuresByPosition,
            'featuresBySlug' => $featuresBySlug,
        ];
    }

    /**
     * Get all categories by WebsiteModel.
     */
    public function allCatalogCategories(Website $website, bool $withProducts = false, bool $checkOnlineStatus = false): array
    {
        $locale = $this->coreLocator->request()->getLocale();
        $allCategories = $this->coreLocator->em()->getRepository(Category::class)->findAllByLocale($website, $locale);

        $categoriesByPosition = [];
        $categoriesBySlug = [];
        foreach ($allCategories as $category) {
            $categoriesByPosition[$category->getPosition()] = $category;
            $categoriesBySlug[$category->getSlug()] = $category;
            ksort($categoriesByPosition);
            ksort($categoriesBySlug);
        }

        if (!$withProducts) {
            return $allCategories;
        }

        $products = $this->coreLocator->em()->getRepository(Product::class)->findAllByLocale($website, $locale, $checkOnlineStatus);

        $productsByCategories = [];
        foreach ($products as $product) {
            if ($checkOnlineStatus) {
                foreach ($product->getCategories() as $category) {
                    $productsByCategories[$category->getSlug()][] = $product;
                }
            }
        }

        return [
            'categoriesByPosition' => $categoriesByPosition,
            'categoriesBySlug' => $categoriesBySlug,
            'productsByCategories' => $productsByCategories,
        ];
    }

    /**
     * Check if Product in cart.
     */
    public function productInCart(int $id): string
    {
        foreach ($this->productsInCart() as $product) {
            if (intval($product['id']) === $id) {
                return 'on';
            }
        }

        return 'off';
    }

    /**
     * Get Products in cart.
     */
    public function productsInCart(): array
    {
        $serializer = new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
        $cookiesRequest = $this->coreLocator->request()->cookies->get('cart_list');
        $cookies = !empty($cookiesRequest) && '[object Object]' !== $cookiesRequest ? $serializer->decode($cookiesRequest, 'json') : [];
        $ids = [];
        foreach ($cookies as $key => $cookie) {
            if (!$cookie || in_array($cookie['id'], $ids)) {
                unset($cookies[$key]);
            } else {
                $ids[] = $cookie['id'];
            }
        }

        return $cookies;
    }

    /**
     * Find featureValue by slug.
     */
    public function findFeatureValueBySlug(Feature $feature, string $slug): array
    {
        foreach ($feature->getValues() as $value) {
            if ($value->getSlug() === $slug) {
                return $value;
            }
        }

        return [];
    }

    /**
     * Find FeatureValueProduct by slug and Product.
     */
    public function valueProductBySlugAndProduct(Product $product, string $featureSlug, ?string $valueSlug = null, int $limit = 0): FeatureValueProduct|array|null
    {
        return $this->coreLocator->em()->getRepository(FeatureValueProduct::class)->findByProductAndSlug($product, $featureSlug, $valueSlug, $limit);
    }

    /**
     * Find FeatureValue by Feature, Value slugs and WebsiteModel.
     */
    public function featureValueBySlugs(Website $website, string $slugFeature, string $slugValue): ?FeatureValue
    {
        return $this->coreLocator->em()->getRepository(FeatureValue::class)->findByFeatureAndValue($website, $slugFeature, $slugValue);
    }

    /**
     * Find findValueBySection.
     */
    public function findValueBySection(Product $product, string $section): array
    {
        $arrayOfResult = [];
        foreach ($product->getValues() as $value) {
            if ($value->isDisplayInArray() === $section) {
                $arrayOfResult[] = $value;
            }
        }

        return $arrayOfResult;
    }
}
