<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\Core\WebsiteModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
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
        mixed $entity,
        string $locale,
        string $listingClassname,
        string $classname,
        array $entities = [],
        array $interface = [],
        bool $all = false,
        bool $asIndexView = false,
    ): array {

        if ($asIndexView) {
            $codes = [];
            foreach ($entities as $entity) {
                $codes[$entity->getId()] = $this->coreLocator->request()->get('url');
            }
            return $codes;
        }

        $result = [];
        $entity = $entity instanceof ViewModel ? $entity->entity : $entity;
        $currentWebsite = $this->coreLocator->website();
        $interface = $interface ?: $this->coreLocator->interfaceHelper()->generate($classname);
        $entities = $this->parseEntities($entities);

        $listingClassname = str_replace('Proxies\__CG__\\', '', $listingClassname);
        $cacheKey = $listingClassname.'-'.$locale.'-'.md5(serialize($entities));
        if (isset($this->cache['indexes_pages'][$cacheKey])) {
            return $this->cache['indexes_pages'][$cacheKey];
        }

        if (!empty($interface['indexPage']) && $entity) {

            $codes = [];
            $entitiesById = [];
            foreach ($entities as $item) {
                if ($item) {
                    $entitiesById[$item->getId()] = $item;
                    if (method_exists($item, 'getUrls')) {
                        foreach ($item->getUrls() as $url) {
                            /** @var Url $url */
                            if ($locale === $url->getLocale() && $url->getIndexPage()) {
                                foreach ($url->getIndexPage()->getUrls() as $indexUrl) {
                                    if ($locale === $indexUrl->getLocale()) {
                                        $codes[$item->getId()] = $indexUrl->getCode();
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($entities) && count($codes) === count($entities)) {
                return $this->cache['indexes_pages'][$cacheKey] = $codes;
            }

            $listings = [];
            if (!empty($this->cache['listing'][$listingClassname])) {
                $listings = $this->cache['listing'][$listingClassname];
            } elseif ($currentWebsite) {
                $referListing = new $listingClassname();
                $queryBuilder = $this->coreLocator->em()->getRepository($listingClassname)
                    ->createQueryBuilder('e')
                    ->andWhere('e.website = :website')
                    ->setParameter('website', $currentWebsite->entity);
                if (method_exists($referListing, 'getPosition')) {
                    $queryBuilder->orderBy('e.position', 'ASC');
                }
                $listings = $this->cache['listing'][$listingClassname] = $queryBuilder->getQuery()->getResult();
            }

            $getters = $this->getGetters($interface);
            $listingPages = [];
            $listingEntities = [];
            $mainListing = null;

            foreach ($listings as $listing) {
                $listingId = $listing->getId();
                $website = $listing->getWebsite() ? $listing->getWebsite() : (method_exists($entity, 'getWebsite') ? $entity->getWebsite() : null);
                $website = str_contains(get_class($entity), 'Teaser') ? $currentWebsite->entity : $website;
                if (array_key_exists('pageByAction', $this->cache) && array_key_exists($listingClassname, $this->cache['pageByAction']) && array_key_exists($listingId, $this->cache['pageByAction'][$listingClassname])) {
                    $pageByAction = $this->cache['pageByAction'][$listingClassname][$listingId];
                } else {
                    $pageByAction = $this->cache['pageByAction'][$listingClassname][$listingId] = $this->getPageByAction($website, $locale, $listingClassname, $interface, $listing);
                }
                $listingPages[$listingId] = $pageByAction;
                $propertyGetter = !empty($getters['properties']) ? $getters['properties'] : null;
                $entityGetter = !empty($getters['entities']) ? $getters['entities'] : null;
                $finder = $propertyGetter && method_exists($listing, $propertyGetter) && $entityGetter && $website && $website->getId() === $currentWebsite->id;
                if (!$entities && $finder || $all && $finder) {
                    $findEntities = $this->getEntities($classname, $listing, $propertyGetter, $entityGetter);
                    foreach ($findEntities as $findEntity) {
                        $entitiesById[$findEntity->getId()] = $findEntity;
                    }
                }
                foreach ($entitiesById as $item) {
                    if ($this->inListing($listing, $item, $classname)) {
                        $listingEntities[$listingId][$item->getId()] = $item;
                    }
                }
                if (!$mainListing && method_exists($listing, 'getCategories') && $listing->getCategories()->isEmpty()) {
                    $mainListing = $listing;
                }
            }

            foreach ($entitiesById as $item) {
                $this->entityListingCount = 0;
                $result[$item->getId()] = $this->getUrlCode($listingEntities, $listingPages, $item, $locale);
            }

            if ($mainListing) {
                $mainListingId = $mainListing->getId();
                foreach ($entitiesById as $item) {
                    if (empty($result[$item->getId()])) {
                        $listingEntities[$mainListingId][$item->getId()] = $item;
                        $this->entityListingCount = 0;
                        $result[$item->getId()] = $this->getUrlCode($listingEntities, $listingPages, $item, $locale);
                    }
                }
            }

            $this->cache['indexes_pages'][$listingClassname][$locale][md5(serialize($entities))] = $result;
        }

        return $result;
    }

    /**
     * To set index page.
     *
     * @throws NonUniqueResultException
     */
    public function updateIndexPages(array $interface, WebsiteModel $websiteModel): void
    {
        if (!empty($interface['listingClass']) && $interface['entity'] && method_exists($interface['entity'], 'getUrls')) {
            $noIndexPagesEntities = $this->coreLocator->em()->getRepository($interface['classname'])
                ->createQueryBuilder('e')
                ->leftJoin('e.urls', 'u')
                ->andWhere('u.indexPage IS NULL')
                ->andWhere('e.website = :website')
                ->setParameter('website', $websiteModel->entity)
                ->getQuery()
                ->getResult();
            if ($noIndexPagesEntities) {
                $flush = false;
                foreach ($websiteModel->configuration->allLocales as $locale) {
                    $indexes = $this->coreLocator->listingService()->indexesPages($interface['entity'], $locale, $interface['listingClass'], $interface['classname'], $noIndexPagesEntities);
                    foreach ($noIndexPagesEntities as $entity) {
                        foreach ($entity->getUrls() as $url) {
                            if (!$url->getIndexPage() && $locale === $url->getLocale() && !empty($indexes[$entity->getId()])) {
                                $page = $this->coreLocator->em()->getRepository(Page::class)->findByUrlCodeAndLocale($websiteModel, $indexes[$entity->getId()], $url->getLocale(), true);
                                if ($page) {
                                    $url->setIndexPage($page);
                                    $this->coreLocator->em()->persist($url);
                                    $flush = true;
                                }
                            }
                        }
                    }
                }
                if ($flush) {
                    $this->coreLocator->em()->flush();
                }
            }
        }
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

        if (method_exists($listing, 'getCategories') && method_exists($entity, 'getCategory') && is_object($entity->getCategory())) {
            foreach ($listing->getCategories() as $category) {
                if ($category->getId() === $entity->getCategory()->getId()) {
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
                    if (!in_array($entity->getCatalog()->getId(), $listingCatalogsIds)) {
                        $inCatalog = false;
                    }
                }
                if (in_array($category->getId(), $listingCategoriesIds) && $inCatalog) {
                    return true;
                }
            }
        } elseif ($isCategory) {
            foreach ($listing->getCategories() as $category) {
                if ($category->getId() === $entity->getId()) {
                    return true;
                }
            }
        } elseif (method_exists($listing, 'getCategories') && 0 === $listing->getCategories()->count()) {
            return true;
        }

        if (method_exists($listing, 'getCatalogs') && method_exists($entity, 'getCatalog') && $listing->getCategories()->isEmpty()) {
            $listingCatalogsIds = [];
            foreach ($listing->getCatalogs() as $catalog) {
                $listingCatalogsIds[] = $catalog->getId();
            }
            if (in_array($entity->getCatalog()->getId(), $listingCatalogsIds)) {
                return true;
            }
        }

        return null;
    }

    /**
     * Get Teaser entities.
     *
     * @throws NonUniqueResultException|\Exception
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
    private function getEntities(string $classname, mixed $parent, string $propertyGetter, string $entityGetter): array
    {
        $entities = [];
        $propertiesCount = 0;

        if (method_exists($parent, $propertyGetter) && $parent->$propertyGetter()->count() > 0) {
            foreach ($parent->$propertyGetter() as $property) {
                if (method_exists($property, $entityGetter)) {
                    foreach ($property->$entityGetter() as $entity) {
                        $entities[$entity->getId()] = $entity;
                    }
                }
                ++$propertiesCount;
            }
        } else {
            $referEntity = new $classname();
            $qb = $this->coreLocator->em()->getRepository($classname)->createQueryBuilder('e')
                ->leftJoin('e.website', 'w')
                ->andWhere('e.website = :website')
                ->setParameter('website', $parent->getWebsite());
            if (method_exists($referEntity, 'getUrls')) {
                $qb->leftJoin('e.urls', 'u')
                    ->addSelect('u');
            }
            $entitiesDb = $qb->getQuery()->getResult();
            foreach ($entitiesDb as $entity) {
                $entities[$entity->getId()] = $entity;
            }
        }

        ksort($entities);

        return $entities;
    }

    /**
     * Get locale Page Url code.
     */
    private function getUrlCode(array $listingEntities, array $listingPages, mixed $entity, string $locale): ?string
    {
        if (is_object($entity) && method_exists($entity, 'getUrls')) {
            foreach ($entity->getUrls() as $url) {
                /** @var Url $url */
                if ($url->getLocale() === $locale && $url->getIndexPage() && $url->isOnline()) {
                    foreach ($url->getIndexPage()->getUrls() as $pageUrl) {
                        if ($pageUrl->getLocale() === $locale) {
                            return $pageUrl->getCode();
                        }
                    }
                }
            }
        }
        foreach ($listingEntities as $listingId => $listingProperties) {
            if (!empty($listingProperties[$entity->getId()]) && count($listingProperties) > $this->entityListingCount) {
                $this->entityListingCount = count($listingProperties);
                return $listingPages[$listingId];
            }
        }

//        return count($listingPages) > 0 ? $listingPages[array_key_first($listingPages)] : null;
        return null;
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
     * @throws \Exception
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
     * Get Page by Action.
     */
    private function getPageByAction(Website $website, string $locale, string $classname, array $interface, mixed $entity): ?string
    {
        //        if (method_exists($entity, 'getPage') && $entity->getPage() instanceof Page) {
        //            $page = $entity->getPage();
        //        } else {
        /** @var Page $page */
        $page = $this->coreLocator->em()->getRepository(Page::class)->findByAction(
            $website,
            $locale,
            $classname,
            $entity->getId(),
            $interface['name'].'-index'
        );
        //        }

        if ($page) {
            foreach ($page->getUrls() as $pageUrl) {
                if ($pageUrl->getLocale() === $locale && $pageUrl->isOnline()) {
                    return $pageUrl->getCode();
                }
            }
        }

        return null;
    }

    /**
     * To shuffle array.
     *
     * @throws \Exception
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
