<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ListingService.
 *
 * Manage Listing entities
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ListingService::class, 'key' => 'listing_service'],
])]
class ListingService
{
    private array $cache = [];
    private int $entityListingCount = 0;

    /**
     * ListingService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get indexes pages by Teaser.
     *
     * @throws NonUniqueResultException
     */
    public function indexesPages(
        string $locale,
        string $listingClassname,
        string $classname,
        array $interface = [],
    ): array {

        if (array_key_exists('indexes_pages', $this->cache) && array_key_exists($listingClassname, $this->cache['indexes_pages'])) {
            return $this->cache['indexes_pages'][$listingClassname];
        }

        $interface = $interface ?: $this->coreLocator->interfaceHelper()->generate($classname);
        $website = $this->coreLocator->website()->entity;
        $listings = $this->listings($listingClassname, $website);
        $pagesGroupByListingIds = $this->pages($listings, $listingClassname, $locale, $website);
        $indexUrls = $this->indexUrls($pagesGroupByListingIds, $listingClassname, $locale);
        $entities = $this->entities($classname, $website, $interface);

        $entitiesDataCache = [];
        foreach ($entities as $e) {
            $eId = $e->getId();
            $entitiesDataCache[$eId] = [
                'category_id' => method_exists($e, 'getCategory') && $e->getCategory() ? $e->getCategory()->getId() : null,
                'categories_ids' => [],
                'catalog_id' => method_exists($e, 'getCatalog') && $e->getCatalog() ? $e->getCatalog()->getId() : null,
            ];
            if (!$entitiesDataCache[$eId]['category_id'] && method_exists($e, 'getMainCategory') && $e->getMainCategory()) {
                $entitiesDataCache[$eId]['category_id'] = $e->getMainCategory()->getId();
            }
            if (method_exists($e, 'getCategories')) {
                foreach ($e->getCategories() as $cat) {
                    $entitiesDataCache[$eId]['categories_ids'][] = $cat->getId();
                }
            }
        }

        $listingsDataCache = [];
        foreach ($listings as $listing) {
            $lId = $listing->getId();
            $listingsDataCache[$lId] = [
                'categories_ids' => [],
                'catalogs_ids' => [],
                'has_catalogs' => method_exists($listing, 'getCatalogs'),
                'catalogs_empty' => true,
                'specificity' => 0,
            ];
            $hasCategories = false;
            if (method_exists($listing, 'getCategories')) {
                $cats = $listing->getCategories();
                foreach ($cats as $cat) {
                    $listingsDataCache[$lId]['categories_ids'][] = $cat->getId();
                }
                $hasCategories = !$cats->isEmpty();
            }
            $hasCatalogs = false;
            if ($listingsDataCache[$lId]['has_catalogs']) {
                $catalogs = $listing->getCatalogs();
                $listingsDataCache[$lId]['catalogs_empty'] = $catalogs->isEmpty();
                foreach ($catalogs as $catalog) {
                    $listingsDataCache[$lId]['catalogs_ids'][] = $catalog->getId();
                }
                $hasCatalogs = !$catalogs->isEmpty();
            }

            // Ordre de priorité :
            // 1. Si match sur catalog et catégorie (plus haute spécificité)
            // 2. Si match sur catalogue (spécificité moyenne)
            // 3. Si match sur categorie (plus basse spécificité)
            if ($hasCatalogs && $hasCategories) {
                $listingsDataCache[$lId]['specificity'] = 3;
            } elseif ($hasCatalogs) {
                $listingsDataCache[$lId]['specificity'] = 2;
            } elseif ($hasCategories) {
                $listingsDataCache[$lId]['specificity'] = 1;
            }
        }

        // Sort listings by specificity (descending) so the most specific one matches first
        usort($listings, function($a, $b) use ($listingsDataCache) {
            $lIdA = $a->getId();
            $lIdB = $b->getId();
            $specA = $listingsDataCache[$lIdA]['specificity'] ?? 0;
            $specB = $listingsDataCache[$lIdB]['specificity'] ?? 0;
            if ($specA !== $specB) {
                return ($specA > $specB) ? -1 : 1;
            }
            // Si même spécificité, on peut utiliser l'ID comme tie-break pour la stabilité
            return ($lIdA > $lIdB) ? -1 : 1;
        });

        $entitiesIndex = [];
        $matches = explode('\\', $classname);
        $isCategory = 'Category' === end($matches);

        foreach ($listings as $listing) {
            $lId = $listing->getId();
            $indexPage = !empty($pagesGroupByListingIds[$lId]) ? $pagesGroupByListingIds[$lId] : null;
            $indexUrl = $indexPage && !empty($indexUrls[$indexPage->getId()]) ? $indexUrls[$indexPage->getId()] : null;
            if ($indexUrl) {
                $lData = $listingsDataCache[$lId];
                foreach ($entities as $entity) {
                    $eId = $entity->getId();
                    // If we already have a match for this entity, skip (since listings are sorted by specificity)
                    if (isset($entitiesIndex[$eId])) {
                        continue;
                    }
                    $eData = $entitiesDataCache[$eId];
                    if ($this->inListingFast($listing, $entity, $lData, $eData, $isCategory)) {
                        $entitiesIndex[$eId] = $indexUrl;
                    }
                }
            }
        }

        $this->cache['indexes_pages'][$listingClassname] = $entitiesIndex;

        return $entitiesIndex;
    }

    private function inListingFast(
        mixed $listing,
        mixed $entity,
        array $lData,
        array $eData,
        bool $isCategory
    ): bool {

        $eId = $entity->getId();
        $cacheKey = $listing->getId().'_'.$eId;

        if (array_key_exists('inListing', $this->cache) && array_key_exists($cacheKey, $this->cache['inListing'])) {
            return (bool) $this->cache['inListing'][$cacheKey];
        }

        $hasCategoriesFilters = !empty($lData['categories_ids']);
        $hasCatalogsFilters = !empty($lData['catalogs_ids']);

        // 1. Si aucun filtre n'est défini, le listing match tout
        if (!$hasCategoriesFilters && !$hasCatalogsFilters) {
            return $this->cache['inListing'][$cacheKey] = true;
        }

        $matchCategory = false;
        $matchCatalog = false;

        // 2. Vérification Catégories
        if ($hasCategoriesFilters) {
            if ($isCategory) {
                $matchCategory = in_array($eId, $lData['categories_ids']);
            } else {
                if ($eData['category_id'] && in_array($eData['category_id'], $lData['categories_ids'])) {
                    $matchCategory = true;
                } elseif (!empty($eData['categories_ids'])) {
                    foreach ($eData['categories_ids'] as $catId) {
                        if (in_array($catId, $lData['categories_ids'])) {
                            $matchCategory = true;
                            break;
                        }
                    }
                }
            }
        }

        // 3. Vérification Catalogs
        if ($hasCatalogsFilters) {
            if ($eData['catalog_id'] !== null && in_array($eData['catalog_id'], $lData['catalogs_ids'])) {
                $matchCatalog = true;
            }
        }

        // 4. Logique de décision
        // S'il y a les deux filtres, il faut matcher les deux (ET)
        if ($hasCategoriesFilters && $hasCatalogsFilters) {
            $res = $matchCategory && $matchCatalog;
        } elseif ($hasCatalogsFilters) {
            // S'il n'y a que le filtre catalogue
            $res = $matchCatalog;
        } else {
            // S'il n'y a que le filtre catégorie
            $res = $matchCategory;
        }

        return $this->cache['inListing'][$cacheKey] = $res;
    }

    private function listings(string $listingClassname, ?Website $website = null): array
    {
        $listings = [];
        if (array_key_exists('listing', $this->cache) && array_key_exists($listingClassname, $this->cache['listing'])) {
            $listings = $this->cache['listing'][$listingClassname];
        } elseif (empty($listings) && $website) {
            $referListing = new $listingClassname();
            $queryBuilder = $this->coreLocator->em()->getRepository($listingClassname)
                ->createQueryBuilder('e')
                ->andWhere('e.website = :website')
                ->setParameter('website', $website);
            if (method_exists($referListing, 'getCategories')) {
                $queryBuilder->leftJoin('e.categories', 'lc')->addSelect('lc');
            }
            if (method_exists($referListing, 'getCatalogs')) {
                $queryBuilder->leftJoin('e.catalogs', 'lca')->addSelect('lca');
            }

            if (method_exists($referListing, 'getPosition')) {
                $queryBuilder->orderBy('e.position', 'ASC');
            }
            $listings = $this->cache['listing'][$listingClassname] = $queryBuilder->getQuery()->getResult();
        }
        return $listings;
    }

    private function pages(array $listings, string $listingClassname, string $locale, ?Website $website = null): array
    {
        $idsToListingMapping = [];
        foreach ($listings as $listing) {
            $idsToListingMapping[] = $listing->getId();
        }

        $this->cache['pages'][$listingClassname][$locale] = !empty($this->cache['pages'][$listingClassname][$locale])
            ? $this->cache['pages'][$listingClassname][$locale]
            : $this->coreLocator->em()->getRepository(Page::class)->findAllByAction($website, $locale, $listingClassname, $idsToListingMapping);

        return $this->cache['pages'][$listingClassname][$locale];
    }

    private function indexUrls(array $pages, string $listingClassname, string $locale): array
    {
        $pageIds = [];
        foreach ($pages as $page) {
            $pageIds[] = $page->getId();
        }
       return $this->coreLocator->em()->getRepository(Page::class)->findPagesIndexByUrl($pageIds, $listingClassname, $locale);
    }

    /**
     * To parse entities.
     */
    private function parseEntities(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            if (is_array($entity)) {
                foreach ($entity as $subEntity) {
                    $result[] = $subEntity;
                }
            } else {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * To parse entities.
     */
    private function inListing(mixed $listing, mixed $entity, string $classname): ?bool
    {
        $matches = explode('\\', $classname);
        $isCategory = 'Category' === end($matches);

        if (array_key_exists('inListing', $this->cache) && array_key_exists($entity->getId(), $this->cache['inListing'])) {
            return $this->cache['inListing'][$entity->getId()];
        }

        if (method_exists($listing, 'getCategories') && method_exists($entity, 'getCategory') && is_object($entity->getCategory())) {
            foreach ($listing->getCategories() as $category) {
                if ($category->getId() === $entity->getCategory()->getId()) {
                    $this->cache['inListing'][$entity->getId()] = true;
                    return true;
                }
            }
        } elseif (method_exists($listing, 'getCategories') && method_exists($entity, 'getCategories')) {
            $listingCategoriesIds = [];
            foreach ($listing->getCategories() as $category) {
                $listingCategoriesIds[] = $category->getId();
            }
            $inCatalog = true;
            foreach ($entity->getCategories() as $category) {
                if (method_exists($listing, 'getCatalogs') && method_exists($entity, 'getCatalog')) {
                    $listingCatalogsIds = [];
                    foreach ($listing->getCatalogs() as $catalog) {
                        $listingCatalogsIds[] = $catalog->getId();
                    }
                    if ($entity->getCatalog() && !in_array($entity->getCatalog()->getId(), $listingCatalogsIds)) {
                        $inCatalog = false;
                    }
                }
                if ((in_array($category->getId(), $listingCategoriesIds) && $inCatalog)
                    || ($listing->getCatalogs()->isEmpty() && in_array($category->getId(), $listingCategoriesIds))
                ) {
                    $this->cache['inListing'][$entity->getId()] = true;
                    return true;
                }
            }
        } elseif ($isCategory) {
            foreach ($listing->getCategories() as $category) {
                if ($category->getId() === $entity->getId()) {
                    $this->cache['inListing'][$entity->getId()] = true;
                    return true;
                }
            }
        } elseif (method_exists($listing, 'getCategories') && 0 === $listing->getCategories()->count()) {
            $this->cache['inListing'][$entity->getId()] = true;
            return true;
        }

        if (method_exists($listing, 'getCatalogs') && method_exists($entity, 'getCatalog') && $listing->getCategories()->isEmpty()) {
            $listingCatalogsIds = [];
            foreach ($listing->getCatalogs() as $catalog) {
                $listingCatalogsIds[] = $catalog->getId();
            }
            if (in_array($entity->getCatalog()->getId(), $listingCatalogsIds)) {
                $this->cache['inListing'][$entity->getId()] = true;
                return true;
            }
        }

        return null;
    }

    /**
     * Get Teaser entities.
     *
     * @throws NonUniqueResultException|Exception
     */
    public function findTeaserEntities(mixed $teaser, string $locale, string $classname, ?WebsiteModel $website = null, bool $all = false, array $joins = []): array
    {
        $website = $website ?: $this->coreLocator->website();
        $queryParams = $this->getQueryParams($teaser, $classname, $all);
        $haveCategories = method_exists($teaser, 'getCategories') && $teaser->getCategories()->count() > 0;
        $cardEntity = !empty($queryParams['interface']['classname']) ? new $queryParams['interface']['classname']() : null;
        $cardCategoryProperty = is_object($cardEntity) && method_exists($cardEntity, 'getCategories') ? 'categories' : 'category';
        $referEntity = new $classname();

        $queryBuilder = $this->optimizedQueryBuilder($queryParams['getters']['property'], $classname, $locale, $website, $queryParams['sort'], $queryParams['order'], false, $teaser)
            ->setMaxResults($queryParams['limit'])
            ->leftJoin('e.'.$queryParams['getters']['property'], $queryParams['getters']['property'])
            ->addSelect($queryParams['getters']['property']);

        if ($teaser instanceof \App\Entity\Module\Catalog\Teaser && !$teaser->getProducts()->isEmpty()) {
            $productsIds = [];
            foreach ($teaser->getProducts() as $product) {
                $productsIds[] = $product->getId();
            }
            $queryBuilder->andWhere('e.id IN (:productsIds)')
                ->setParameter('productsIds', $productsIds);
        }

        if ($teaser->isPromote() && method_exists($referEntity, 'isPromote')) {
            $queryBuilder->andWhere('e.promote = :promote')
                ->setParameter('promote', true);
        }

        if (!empty($joins)) {
            foreach ($joins as $name => $join) {
                $joinRelations = [];
                if (!is_int($name)) {
                    $joinRelations = $join;
                    $join = $name;
                }
                $matches = explode('\\', $classname);
                $endClassname = end($matches);
                $joinKeyName = strtolower($endClassname).ucfirst($join);
                $queryBuilder->leftJoin('e.'.$join, $joinKeyName)
                    ->addSelect($joinKeyName);
                foreach ($joinRelations as $joinRelation) {
                    $joinRelationKeyName = $joinRelation.ucfirst($joinKeyName);
                    $queryBuilder->leftJoin($joinKeyName.'.'.$joinRelation, $joinRelationKeyName)
                        ->addSelect($joinRelationKeyName);
                }
            }
        }

        if (method_exists($teaser, 'getSubCategories') && $teaser->getSubCategories()->count() > 0) {
            $subCategoryIds = [];
            foreach ($teaser->getSubCategories() as $subCategory) {
                $subCategoryIds[] = $subCategory->getId();
            }
            if ($subCategoryIds) {
                $queryBuilder->leftJoin('e.subCategories', 'subCat')
                    ->andWhere('subCat.id IN (:subCategoryIds)')
                    ->setParameter('subCategoryIds', $subCategoryIds);
            }
        }

        if ($haveCategories && !$teaser->isMatchCategories()) {
            $categoryIds = [];
            foreach ($teaser->getCategories() as $category) {
                $categoryIds[] = $category->getId();
            }
            if ($categoryIds && 'category' === $cardCategoryProperty) {
                $queryBuilder->andWhere('e.category IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds);
            } elseif ($categoryIds && 'categories' === $cardCategoryProperty && method_exists($referEntity, 'getCategories')) {
                $queryBuilder->leftJoin('e.categories', 'cat')
                    ->andWhere('cat.id IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds);
            } elseif ($categoryIds && 'categories' === $cardCategoryProperty) {
                $queryBuilder->andWhere('categories.id IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds);
            }
        } elseif ($haveCategories && $teaser->isMatchCategories() && 'categories' === $cardCategoryProperty && method_exists($referEntity, 'getCategories')) {
            foreach ($teaser->getCategories() as $category) {
                $queryBuilder->leftJoin('e.categories', 'cat_'.$category->getId());
                $queryBuilder->andWhere('cat_'.$category->getId().'.id = :category_id_'.$category->getId())
                    ->setParameter('category_id_'.$category->getId(), $category->getId());
            }
        }

        $mappingIds = [];
        $getter = $queryParams['getters']['properties'];
        if (method_exists($teaser, $getter)) {
            if ($teaser->$getter() instanceof PersistentCollection) {
                foreach ($teaser->$getter() as $property) {
                    $mappingIds[] = $property->getId();
                }
            } else {
                $mappingIds[] = $teaser->$getter()->getId();
            }
            if ($mappingIds && method_exists($referEntity, $queryParams['getters']['singleProperty'])) {
                $queryBuilder->andWhere('e.'.$queryParams['getters']['property'].' IN (:mappingIds)')
                    ->setParameter('mappingIds', $mappingIds);
            }
        }

        $entities = $queryBuilder->getQuery()->getResult();

        return $this->sortResult($queryParams, $entities);
    }

    /**
     * Get Query params.
     *
     * @throws NonUniqueResultException
     */
    private function getQueryParams(mixed $teaser, string $classname, bool $all = false): array
    {
        $params['limit'] = $all ? 100000000000 : ($teaser->getNbrItems() ? $teaser->getNbrItems() : 5);
        $params['orderBy'] = explode('-', $teaser->getOrderBy());
        $params['sort'] = !empty($params['orderBy'][0]) ? $params['orderBy'][0] : 'publicationStart';
        $params['order'] = !empty($params['orderBy'][1]) ? strtoupper($params['orderBy'][1]) : 'DESC';
        $params['interface'] = $this->coreLocator->interfaceHelper()->generate($classname);
        $params['sortByMapping'] = $params['sort'] == $params['interface']['indexPage'];
        $params['sortMapping'] = $params['sortByMapping'] ? $params['order'] : null;
        $params['sortMapping'] = $params['sortByMapping'] ? 'DESC' : $params['sortMapping'];
        $params['getters'] = $this->getGetters($params['interface']);

        return $params;
    }

    /**
     * Get getters.
     */
    private function getGetters(array $interface): array
    {
        $mappingProperty = str_ends_with($interface['indexPage'], 'y') ? rtrim($interface['indexPage'], 'y').'ies' : $interface['indexPage'].'s';
        $mappingProperty = str_ends_with($interface['indexPage'], 's') ? $interface['indexPage'] : $mappingProperty;
        $mappingEntity = str_ends_with($interface['name'], 'y') ? rtrim($interface['name'], 'y').'ies' : $interface['name'].'s';

        return [
            'property' => $interface['indexPage'],
            'singleProperty' => 'get'.ucfirst($interface['indexPage']),
            'properties' => 'get'.ucfirst($mappingProperty),
            'entity' => 'get'.ucfirst($interface['name']),
            'entities' => 'get'.ucfirst($mappingEntity),
        ];
    }

    /**
     * Get entities.
     */
    private function entities(string $classname, Website $website, array $interface): array
    {
        if (array_key_exists('entities', $this->cache) && array_key_exists($classname, $this->cache['entities'])) {
            return $this->cache['entities'][$classname];
        }

        $referEntity = new $classname();
        $qb = $this->coreLocator->em()->getRepository($classname)->createQueryBuilder('e')
            ->leftJoin('e.website', 'w')
            ->andWhere('e.website = :website')
            ->setParameter('website', $website);

        if (method_exists($referEntity, 'getCategories')) {
            $qb->leftJoin('e.categories', 'c')->addSelect('c');
        }
        if (method_exists($referEntity, 'getCategory')) {
            $qb->leftJoin('e.category', 'cc')->addSelect('cc');
        }
        if (method_exists($referEntity, 'getMainCategory')) {
            $qb->leftJoin('e.mainCategory', 'cm')->addSelect('cm');
        }
        if (method_exists($referEntity, 'getCatalog')) {
            $qb->leftJoin('e.catalog', 'ca')->addSelect('ca');
        }

        $entites = $qb->getQuery()
            ->getResult();

        $this->cache['entities'][$classname] = $entites;

        return $this->cache['entities'][$classname];
    }

    /**
     * PublishedQueryBuilder.
     */
    private function optimizedQueryBuilder(
        string $mappingProperty,
        string $classname,
        string $locale,
        WebsiteModel $website,
        ?string $sort = null,
        ?string $order = null,
        bool $preview = false,
        mixed $configEntity = null,
    ): QueryBuilder {

        $referEntity = new $classname();
        $sort = $sort ?: 'publicationStart';
        $order = $order ?: 'DESC';

        $repository = $this->coreLocator->em()->getRepository($classname);
        $statement = $repository->createQueryBuilder('e')
            ->leftJoin('e.website', 'w')
            ->andWhere('e.website = :website')
            ->setParameter('website', $website->entity)
            ->addSelect('w');

        if (method_exists($referEntity, 'getUrls')) {
            $statement->leftJoin('e.urls', 'u')
                ->leftJoin('u.seo', 's')
                ->andWhere('u.locale = :locale')
                ->setParameter('locale', $locale)
                ->addSelect('u')
                ->addSelect('s');
            if (!$preview) {
                $statement->andWhere('u.online = :online')
                    ->setParameter('online', true);
            }
        }

        $orderByGetter = 'get'.ucfirst($sort);
        if ('random' !== $sort && method_exists($referEntity, $orderByGetter)) {
            $statement->orderBy('e.'.$sort, $order);
        }

        if (method_exists($referEntity, 'getPublicationStart')) {
            $statement->andWhere('e.publicationStart IS NULL OR e.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('e.publicationStart IS NOT NULL');
        }

        if (method_exists($referEntity, 'getPublicationEnd')) {
            $statement->andWhere('e.publicationEnd IS NULL OR e.publicationEnd > CURRENT_TIMESTAMP()');
        }

        $displayPastEvents = $configEntity && property_exists($configEntity, 'pastEvents') && $configEntity->isPastEvents();
        if ($displayPastEvents && 'startDate' === $sort && method_exists($referEntity, 'getStartDate') && !method_exists($referEntity, 'getEndDate')) {
            $statement->andWhere('e.startDate IS NOT NULL');
        } elseif ($configEntity && method_exists($configEntity, 'isAsEvents') && $configEntity->isAsEvents() && 'startDate' === $sort
            && method_exists($referEntity, 'getStartDate') && method_exists($referEntity, 'getEndDate')) {
            if ($displayPastEvents) {
                $statement->andWhere('e.startDate IS NOT NULL');
            } else {
                $statement->andWhere('e.startDate IS NOT NULL AND e.startDate >= CURRENT_TIMESTAMP()')
                    ->andWhere('e.endDate IS NULL OR e.endDate >= CURRENT_TIMESTAMP()');
            }
        } elseif ('startDate' === $sort && method_exists($referEntity, 'getStartDate') && method_exists($referEntity, 'getEndDate')) {
            $statement->andWhere('e.startDate IS NULL OR e.startDate >= CURRENT_TIMESTAMP()')
                ->andWhere('e.endDate IS NULL OR e.endDate <= CURRENT_TIMESTAMP()');
        }

        if ('startDate' === $sort && method_exists($referEntity, 'getStartDate') && !method_exists($referEntity, 'getEndDate')) {
            $statement->andWhere('e.startDate IS NULL OR e.startDate >= CURRENT_TIMESTAMP()');
        }

        return $statement;
    }

    /**
     * To sort result.
     *
     * @throws Exception
     */
    private function sortResult(array $queryParams = [], array $result = []): array
    {
        $response = [];
        $sort = strtolower($queryParams['sort']);
        $sortDates = $queryParams['sort'] && str_contains($sort, 'publication')
            || $queryParams['sort'] && str_contains($sort, 'date');
        $sortCategories = $queryParams['sort'] && str_contains($sort, 'category');
        $sortPositions = $queryParams['sort'] && str_contains($sort, 'position');
        $sortRandom = $queryParams['sort'] && str_contains($sort, 'random');

        if ($sortRandom) {
            $result = $this->shuffleAssoc($result);
        } elseif ($sortPositions) {
            foreach ($result as $value) {
                if (is_iterable($value)) {
                    foreach ($value as $item) {
                        if (method_exists($item, 'getPosition')) {
                            $response[$item->getPosition()][] = $item;
                        }
                    }
                } else {
                    if (method_exists($value, 'getPosition')) {
                        $response[$value->getPosition()][] = $value;
                    }
                }
            }
        } else {
            foreach ($result as $value) {
                if ($sortDates && is_object($value) && method_exists($value, 'getPublicationStart') && str_contains($sort, 'publication') && $value->getPublicationStart() instanceof \DateTime) {
                    $response[$value->getPublicationStart()->format('YmdHis')][$value->getPosition()] = $value;
                    ksort($response[$value->getPublicationStart()->format('YmdHis')]);
                } elseif ($sortDates && is_object($value) && method_exists($value, 'getStartDate') && str_contains($sort, 'date') && $value->getStartDate() instanceof \DateTime) {
                    $response[$value->getStartDate()->format('YmdHis')][$value->getPosition()] = $value;
                    ksort($response[$value->getStartDate()->format('YmdHis')]);
                } elseif ($sortCategories && is_object($value) && method_exists($value, 'getCategory') && $value->getCategory()) {
                    $response[$value->getCategory()->getPosition()][$value->getPosition()] = $value;
                    ksort($response[$value->getCategory()->getPosition()]);
                } elseif (is_iterable($value) && $sortDates) {
                    foreach ($value as $keyValue => $item) {
                        if (is_object($item) && method_exists($item, 'getPublicationStart') && $item->getPublicationStart() instanceof \DateTime) {
                            $response[$item->getPublicationStart()->format('YmdHis')][$item->getPosition()] = $item;
                            ksort($response[$item->getPublicationStart()->format('YmdHis')]);
                        } elseif (is_object($item) && method_exists($item, 'getStartDate') && $item->getStartDate() instanceof \DateTime) {
                            $response[$item->getStartDate()->format('YmdHis')][$item->getPosition()] = $item;
                            ksort($response[$item->getPublicationStart()->format('YmdHis')]);
                        } elseif ($sortCategories && is_object($item) && method_exists($item, 'getCategory') && $item->getCategory()) {
                            $response[$value->getCategory()->getPosition()][$item->getPosition()] = $item;
                            ksort($response[$item->getCategory()->getPosition()]);
                        }
                    }
                }
            }
        }

        if (!$sortRandom) {
            if ($queryParams['sortByMapping'] && 'ASC' === $queryParams['sortMapping']
                || !$queryParams['sortByMapping'] && 'ASC' === $queryParams['order']) {
                ksort($response);
            } elseif ($queryParams['sortByMapping'] && 'DESC' === $queryParams['sortMapping']
                || !$queryParams['sortByMapping'] && 'DESC' === $queryParams['order']) {
                ksort($response, 1);
                $response = array_reverse($response, true);
                krsort($result);
            }
        }

        return $response ?: $result;
    }

    /**
     * To shuffle array.
     *
     * @throws Exception
     */
    private function shuffleAssoc(array $array = []): array
    {
        $random = [];
        while (count($array)) {
            $keys = array_keys($array);
            $index = $keys[random_int(0, count($keys) - 1)];
            $random[$index] = $array[$index];
            if (is_array($random[$index])) {
                shuffle($random[$index]);
            }
            unset($array[$index]);
        }

        return $random;
    }
}
