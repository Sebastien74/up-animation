<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Listing;
use App\Entity\Module\Catalog\ListingFeatureValue;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\SubCategory;
use App\Repository\Module\Catalog\ProductRepository;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;

/**
 * CatalogSearchService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CatalogSearchService implements CatalogSearchServiceInterface
{
    private const string SEARCH_TYPE = 'AND';
    private const bool SUB_CATEGORIES_AS_CATEGORIES_VALUES = false;

    private ProductRepository $productRepository;
    private array $filters;
    private string $filterText = '';
    private string $locale = '';
    private iterable $allProducts = [];
    private iterable $products = [];
    private ?Website $website;
    private ?Listing $listing;
    private bool $updateFields = true;
    private bool $displayAll = false;
    private array $cache = [];
    private array $cacheAll = [];

    /**
     * CatalogSearchService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->productRepository = $this->coreLocator->em()->getRepository(Product::class);
        if ($this->coreLocator->request()) {
            $this->filters = $this->coreLocator->request()->get('products') ? $this->coreLocator->request()->get('products') : $_GET;
            $this->filterText = !empty($this->filters['text']) ? $this->filters['text'] : '';
            $excludedPatterns = ['utm_', 'ajax', 'fbclid', 'text', 'page', 'website'];
            foreach ($this->filters as $key => $value) {
                foreach ($excludedPatterns as $pattern) {
                    if (is_string($key) && str_contains($key, $pattern)) {
                        unset($this->filters[$key]);
                    }
                }
            }
            $this->locale = $this->coreLocator->locale();
        }
    }

    /**
     * Execute service.
     *
     * @throws NonUniqueResultException
     */
    public function execute(Listing $listing, array $data = [], ?string $locale = null): iterable
    {
        $this->website = $listing->getWebsite();
        $this->listing = $listing;
        $this->updateFields = $this->listing->isUpdateFields();
        $this->displayAll = 'all' === $this->listing->getDisplay();
        $this->locale = $locale ?: $this->locale;

        $catalogs = $this->getByCatalogs();
//        $allProducts = $this->products;
        $this->setValues();
        $categories = $this->getByCategories($catalogs);
        $subCategories = $this->getBySubCategories($catalogs);

        //        $this->getByValues();
        //        $allProducts = array_replace($allProducts, $this->products);
        //        $this->cache($this->products, 'products-values');
        //        $this->cache($allProducts, 'products-values', true);

        $productIds = $this->products;
        $this->products = $this->coreLocator->em()->getRepository(Product::class)->findByIds($this->website, $this->locale, $this->products, $this->listing);
//        $this->coreLocator->em()->clear();
        $this->cache['categories'] = !empty($this->cacheAll['categories']) ? $this->cacheAll['categories'] : [];
        //        $this->cache['products-categories'] = !empty($this->cacheAll['products-categories']) ? $this->cacheAll['products-categories'] : [];
        $searchResults = $this->getSearch($data);
        //        $searchResults = $listing->isCombineFieldsText() ? $this->getByText($listing, $searchResults) : $searchResults;

//        $catalogs = [];
//        if ($this->listing->getSearchCatalogs() && $this->listing->getCatalogs()->isEmpty()) {
//            dd($this->listing);
//        } else {
//            foreach ($this->listing->getCatalogs() as $result) {
//
//            }
//        }

        return [
            'initial' => $this->updateFields ? $this->cache : $this->cacheAll,
            //            'initialResults' => $this->updateFields ? $this->products : $allProducts,
            'initialResults' => $this->products,
            'productIds' => $productIds,
            'searchResults' => $searchResults,
            'catalogs' => $catalogs,
            'categories' => $categories,
            'subcategories' => $subCategories,
        ];
    }

    /**
     * To set values.
     */
    private function setValues(): void
    {
        if (empty($this->cache['featureValueProduct'])) {
            $featuresValuesDb = $this->coreLocator->em()->getRepository(FeatureValue::class)->findBy(['website' => $this->website->getId()]);
            foreach ($featuresValuesDb as $key => $featureValue) {
                $featuresValuesDb['feature-'.$featureValue->getId()] = $featureValue;
                unset($featuresValuesDb[$key]);
            }
            foreach ($featuresValuesDb as $key => $featureValue) {
                $featuresValuesDb[$featureValue->getId()] = $featureValue;
                unset($featuresValuesDb[$key]);
            }
            $query = $this->coreLocator->em()->createQuery('SELECT e.id, e.featurePosition, e.displayInArray, IDENTITY(e.value) as valueId, IDENTITY(e.product) as productId FROM '.FeatureValueProduct::class.' e');
            $values = $query->toIterable();
            foreach ($values as $value) {
                if (!empty($featuresValuesDb[$value['valueId']]) && !empty($this->products[$value['productId']])) {
                    $productValue = new FeatureValueProduct();
                    $productValue->setFeaturePosition($value['featurePosition']);
                    $productValue->setDisplayInArray($value['displayInArray']);
                    $productValue->setValue($featuresValuesDb[$value['valueId']]);
                    $productValue->setFeature($featuresValuesDb[$value['valueId']]->getCatalogfeature());
                    $productValue->setProduct($this->products[$value['productId']]);
                    $this->cache['products-values'][$value['productId']][$value['valueId']] = $productValue;
                }
            }
        }
    }

    /**
     * Get by Catalog.
     */
    private function getByCatalogs(): Collection|array
    {
        $catalogs = [];
        if ($this->listing->getCatalogs()->count() > 0 && !$this->displayAll) {
            $catalogs = $this->listing->getCatalogs();
        } elseif ($this->displayAll) {
            $catalogs = $this->coreLocator->em()->getRepository(Catalog::class)->findBy(['website' => $this->website]);
        }

//        $catalogIds = [];
//        foreach ($catalogs as $key => $catalog) {
//            $catalogIds[] = $catalog->getId();
//        }
//        $this->products = $this->coreLocator->em()->getRepository(Product::class)->findBy(['catalog' => $catalogs[0]]);
        $this->products = $this->productRepository->findOnlineByCatalogs($this->website, $this->locale, $catalogs, $this->listing);
        foreach ($this->products as $key => $product) {
            $this->products['product-'.$product->getId()] = $product;
            unset($this->products[$key]);
        }
        foreach ($this->products as $key => $product) {
            $this->products[$product->getId()] = $product;
            unset($this->products[$key]);
        }

//        $query = sprintf('SELECT p.id  FROM %s p JOIN p.catalog c WHERE c.id IN (:catalogIds)', Product::class);
//        $products = $this->coreLocator->em()->createQuery($query)
//            ->setParameter('catalogIds', $catalogIds)
//            ->getResult(AbstractQuery::HYDRATE_ARRAY);
//        $this->products = array_column($products, 'id');
        //        if (!$this->updateFields) {
        //            $this->products($products, true, true);
        //            $this->products($products, true, true);
        //            $this->cache($catalogs, 'catalogs', true);
        //        }
        //        $this->products($products, true);
        //        $this->cache($catalogs, 'catalogs');

        return $catalogs;
    }

    /**
     * Get by categories.
     *
     * @throws NonUniqueResultException
     */
    private function getByCategories(mixed $catalogs): array|Collection
    {
        if (self::SUB_CATEGORIES_AS_CATEGORIES_VALUES) {
            return [];
        }

        $init = is_array($catalogs) ? !$catalogs : $catalogs instanceof PersistentCollection && 0 === $catalogs->count();
        $categories = $categoriesToDisplay = $categoriesForAllProducts = $this->listing->getCategories()->count() > 0 && !$this->displayAll
            ? $this->listing->getCategories() : [];

        // to find all categories if you need
        // $categories = $this->coreLocator->em()->getRepository(Category::class)->findAllByLocale($this->website, $this->locale)

        if (!empty($this->filters['categories'])) {
            /** For links request */
            $categories = $this->coreLocator->em()->getRepository(Category::class)->findBySlug($this->website, $this->locale, $this->filters['categories']);
            //            $categoriesToDisplay = $this->coreLocator->em()->getRepository(Category::class)->findAllByLocale($this->website, $this->locale);
        }

        if ($categoriesForAllProducts) {
            $allProducts = $this->productRepository->findOnlineByCategories($this->website, $this->locale, $categoriesForAllProducts, $catalogs);
            $this->products($allProducts, $init, true);
            $this->cache($categoriesForAllProducts, 'categories', true);
        }

        $categoriesToDisplay = is_array($categoriesToDisplay) ? $categoriesToDisplay : $categoriesToDisplay->getValues();

        foreach ($categoriesToDisplay as $key => $category) {
            $productsByCategory = $this->productRepository->findOnlineByCategories($this->website, $this->locale, [$category], $catalogs);
            if (empty($productsByCategory)) {
                unset($categoriesToDisplay[$key]);
            }
        }

        $categories = $categories instanceof PersistentCollection ? $categories->toArray() : $categories;

        if ($categories) {
            $products = $this->productRepository->findOnlineByCategories($this->website, $this->locale, $categories, $catalogs);
            $productsIds = $this->products($products, $init);
            foreach ($this->products as $key => $product) {
                if (!in_array($product->getId(), $productsIds)) {
                    unset($this->products[$key]);
                }
            }
        }

        $this->cache($categories, 'categories');

        return $categoriesToDisplay;
    }

    /**
     * Get by categories.
     *
     * @throws NonUniqueResultException
     */
    private function getBySubCategories(mixed $catalogs): array|Collection
    {
        $subCategories = $subCategoriesToDisplay = $this->listing->getSubCategories()->count() > 0 && !$this->displayAll
            ? $this->listing->getSubCategories() : $this->coreLocator->em()->getRepository(SubCategory::class)->findByWebsite($this->website);
        if (!empty($this->filters['categories'])) {
            $subCategories = $subCategoriesToDisplay = $this->coreLocator->em()->getRepository(SubCategory::class)->findByCategorySlugAndWebsite($this->filters['categories'], $this->website);
            if (!empty($this->filters['subcategories'])) {
                $subCategories = $this->coreLocator->em()->getRepository(SubCategory::class)->findBySlugAndWebsite($this->filters['subcategories'], $this->website);
            }
        } elseif (!empty($this->filters['subcategories'])) {
            $subCategories = $this->coreLocator->em()->getRepository(SubCategory::class)->findBySlugAndWebsite($this->filters['subcategories'], $this->website);
        }

        if ($subCategories) {
            $productsByDisplay = $this->productRepository->findOnlineByCategories($this->website, $this->locale, [], $catalogs, false, false, $subCategoriesToDisplay);
            $products = $this->productRepository->findOnlineByCategories($this->website, $this->locale, [], $catalogs, false, false, $subCategories);
            $productsIds = [];
            foreach ($products as $product) {
                $productsIds[] = $product->getId();
            }
            foreach ($this->products as $key => $product) {
                if (!in_array($product->getId(), $productsIds)) {
                    unset($this->products[$key]);
                }
            }
            $subCategoriesIdsProducts = [];
            foreach ($productsByDisplay as $product) {
                foreach ($product->getSubCategories() as $subCategory) {
                    if (!in_array($subCategory->getId(), $subCategoriesIdsProducts)) {
                        $subCategoriesIdsProducts[] = $subCategory->getId();
                    }
                }
            }
            $subCategoriesToDisplay = $this->coreLocator->em()->getRepository(SubCategory::class)->findByIds($this->website, $subCategoriesIdsProducts);
        }

        return $subCategoriesToDisplay;
    }

    /**
     * Get by Values.
     */
    private function getByValues(): void
    {
        $featuresValues = [];
        if ($this->listing->getFeatures()->count() > 0 && !$this->displayAll && 0 === $this->listing->getFeaturesValues()->count()) {
            $featuresValues = $this->listing->getFeatures();
        } elseif (!$this->displayAll && $this->listing->getFeaturesValues()->count() > 0) {
            $featuresValues = $this->listing->getFeaturesValues();
        } elseif ($this->displayAll) {
            $featuresValues = $this->coreLocator->em()->getRepository(FeatureValue::class)->findBy(['website' => $this->website]);
        }

        $valuesForAllProducts = $this->values($featuresValues, true);
        if ($valuesForAllProducts && !$this->updateFields) {
            $allProducts = $this->productRepository->findOnlineByValues($this->website, $this->locale, $valuesForAllProducts, 'OR');
            $this->products($allProducts, false, true);
        }

        $values = $this->values($featuresValues);
        if ($values) {
            $products = $this->productRepository->findOnlineByValues($this->website, $this->locale, $values, 'OR');
            $this->products($products);
        }
    }

    /**
     * Get by text.
     */
    private function getByText(Listing $listing, iterable $searchResults): iterable
    {
        $products = !empty($this->filterText)
            ? $this->coreLocator->em()->getRepository(Product::class)->findLikeInTitle($this->website, $this->locale, $this->filterText, $listing)
            : $searchResults;
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product->getId();
        }
        foreach ($searchResults as $key => $product) {
            if (!in_array($product->getId(), $ids)) {
                unset($searchResults[$key]);
            }
        }

        return $searchResults;
    }

    /**
     * Set Values.
     */
    private function values($values, bool $allProducts = false): array
    {
        $result = [];
        foreach ($values as $value) {
            if ($value instanceof Feature) {
                foreach ($value->getValues() as $listingValue) {
                    $result[] = $listingValue;
                }
            } elseif ($value instanceof ListingFeatureValue) {
                $result[] = $value->getValue();
            } else {
                $result[] = $value;
            }
        }
        $this->cache($result, 'values', $allProducts);

        return $result;
    }

    /**
     * To set Product[].
     */
    private function products(iterable $products, bool $init = false, bool $allProducts = false): array
    {
        $productsIds = [];
        foreach ($products as $key => $product) {
            $productsIds[] = $product->getId();
            $this->products[] = $product;
        }

        return $productsIds;

        //        $property = $allProducts ? 'allProducts' : 'products';
        //
        //        if (!$this->$property && $init) {
        //            foreach ($parseProducts as $product) {
        //                $this->$property[$product->getId()] = $product;
        //                $this->cache['productsIds'][] = $product->getId();
        //            }
        //        }
        //
        //        foreach ($this->$property as $product) {
        //            $match = false;
        //            foreach ($parseProducts as $productToParse) {
        //                if ($product->getId() === $productToParse->getId()) {
        //                    $match = true;
        //                    break;
        //                }
        //            }
        //            if (!$match) {
        //                unset($this->$property[$product->getId()]);
        //            }
        //        }
    }

    /**
     * To set cache result.
     */
    private function cache($entities, string $keyName = '', bool $allProducts = false): void
    {
        //        $property = $allProducts ? 'cacheAll' : 'cache';
        //        foreach ($entities as $entity) {
        //            if ($entity instanceof Product) {
        //                if (!empty($this->cache['products-values'][$entity->getId()])) {
        //                    foreach ($this->cache['products-values'][$entity->getId()] as $value) {
        //                        if ($value->getValue() instanceof FeatureValue) {
        //                            $this->$property[$keyName][$value->getValue()->getSlug()][] = $value;
        //                        }
        //                    }
        //                    ksort($this->$property[$keyName]);
        //                }
        //                foreach ($entity->getCategories() as $category) {
        //                    $this->$property['products-categories'][$category->getSlug()][$entity->getId()] = $entity;
        //                }
        //                $mainCategory = $entity->getMainCategory();
        //                if ($mainCategory && empty($this->$property['products-categories'][$mainCategory->getSlug()])) {
        //                    $this->$property['products-categories'][$mainCategory->getSlug()][$entity->getId()] = $entity;
        //                }
        //                foreach ($entity->getSubCategories() as $subCategory) {
        //                    $this->$property['products-subcategories'][$subCategory->getSlug()][$entity->getId()] = $entity;
        //                }
        //            } elseif ($entity instanceof FeatureValue) {
        //                $this->$property[$keyName][$entity->getCatalogfeature()->getSlug()][$entity->getSlug()] = $entity;
        //                ksort($this->$property[$keyName][$entity->getCatalogfeature()->getSlug()]);
        //            } else {
        //                $this->$property[$keyName][$entity->getId()] = $entity;
        //                ksort($this->$property[$keyName]);
        //            }
        //        }
        //        if (empty($this->$property[$keyName])) {
        //            $this->$property[$keyName] = [];
        //        }
    }

    /**
     * Get search result.
     */
    private function getSearch(array $data): iterable
    {
        $searchResult = $this->products;
        if ($data) {
            $productIds = $this->getProductIds($data);
            $searchResult = $this->result($productIds, $searchResult, $data);
        }

        return $searchResult;
    }

    /**
     * Get Product[] by ids.
     */
    private function getProductIds(array $data): array
    {
        $categoryProductIds = [];
        $subCategoryProductIds = [];
        $catalogProductIds = [];
        $valueProductIds = [];

        foreach ($data as $fields) {
            if (is_iterable($fields)) {
                foreach ($fields as $key => $value) {
                    foreach ($this->products as $product) {
                        if ('categories' === $key && !empty($this->cache['products-categories'][$value])
                            || 'filters' === $key && is_array($value) && !empty($value['categories'])) {
                            $categories = $product->getCategories();
                            foreach ($categories as $category) {
                                $categoryProductIds[Urlizer::urlize($category->getSlug())][$product->getId()] = $product;
                            }
                        } elseif (str_contains($key, 'subcategories') || ('filters' === $key && is_array($value) && !empty($value['subcategories']))) {
                            $subCategories = $product->getSubCategories();
                            foreach ($subCategories as $subCategory) {
                                $subCategoryProductIds[Urlizer::urlize($subCategory->getSlug())][$product->getId()] = $product;
                            }
                        } elseif ('catalogs' === $key) {
                            $catalogProductIds[Urlizer::urlize($product->getCatalog()->getSlug())][$product->getId()] = $product;
                        } elseif ('categories' !== $key) {
                            foreach ($product->getValues() as $value) {
                                $productValue = $value->getValue();
                                if ($productValue) {
                                    $slug = 'true' == $productValue->getSlug() ? '1' : ('true' == $productValue->getSlug() ? '0' : $productValue->getSlug());
                                    $valueProductIds[Urlizer::urlize($productValue->getCatalogfeature()->getSlug())][$slug][$product->getId()] = $product;
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'categories' => $categoryProductIds,
            'subcategories' => $subCategoryProductIds,
            'catalogs' => $catalogProductIds,
            'values' => $valueProductIds,
        ];
    }

    /**
     * To set result.
     */
    private function result(array $productIds, array $searchResult, array $data): array
    {
        $categories = !empty($data['categories']) ? $data['categories'] : (!empty($data['filters']['categories']) ? $data['filters']['categories'] : null);
        if ($categories) {
            foreach ($this->products as $key => $product) {
                if (empty($productIds['categories'][$categories][$product->getId()])) {
                    unset($searchResult[$key]);
                }
            }
        }

        $subCategories = !empty($data['subcategories']) ? [$data['subcategories']] : (!empty($data['filters']['subcategories']) ? [$data['filters']['subcategories']] : []);
        if (!$subCategories) {
            $dataToParse = !empty($data['filters']) ? $data['filters'] : $data;
            foreach ($dataToParse as $key => $value) {
                if (str_contains($key, 'subcategories')) {
                    $subCategories[] = $value;
                }
            }
        }
        if ($subCategories) {
            foreach ($this->products as $key => $product) {
                $matches = 0;
                foreach ($subCategories as $subCategory) {
                    if (!empty($productIds['subcategories'][$subCategory][$product->getId()])) {
                        ++$matches;
                    }
                }
                if ($matches !== count($subCategories)) {
                    unset($searchResult[$key]);
                }
            }
        }

        $catalogs = !empty($data['catalogs']) ? $data['catalogs'] : (!empty($data['filters']['catalogs']) ? $data['filters']['catalogs'] : null);
        if ($catalogs) {
            foreach ($this->products as $key => $product) {
                if (empty($productIds['catalogs'][$catalogs][$product->getId()])) {
                    unset($searchResult[$key]);
                }
            }
        }

        $valuesSearch = [];
        foreach ($this->filters as $name => $value) {
            if ('subcategories' !== $name && 'categories' !== $name && 'catalogs' !== $name && !empty($value)) {
                $valuesSearch[$name][] = $value;
            }
        }

        $countValuesSearch = 0;
        foreach ($valuesSearch as $values) {
            foreach ($values as $slug) {
                $countValuesSearch = is_iterable($slug) ? $countValuesSearch + count($slug) : $countValuesSearch + 1;
            }
        }

        if (!empty($productIds['values']) && !empty($valuesSearch)) {
            foreach ($searchResult as $key => $product) {
                $countValues = 0;
                foreach ($valuesSearch as $featureName => $values) {
                    foreach ($values as $slug) {
                        if (!is_array($slug) && !empty($productIds['values'][$featureName][$slug][$product->getId()])) {
                            ++$countValues;
                        } elseif (is_array($slug)) {
                            foreach ($slug as $subSlug) {
                                if (!empty($productIds['values'][$featureName][$subSlug][$product->getId()])) {
                                    ++$countValues;
                                }
                            }
                        }
                    }
                }
                if ($countValues < $countValuesSearch) {
                    unset($searchResult[$key]);
                }
            }
        }

        return $searchResult;
    }
}