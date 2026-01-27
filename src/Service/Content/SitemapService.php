<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;

/**
 * SitemapService.
 *
 * Manage WebsiteModel sitemap
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SitemapService
{
    private bool $forFront = false;
    private ?string $locale = null;
    private ?string $host = null;
    private WebsiteModel $website;
    private ConfigurationModel $configuration;
    private array $localesWebsites = [];
    private array $urls = [];
    private array $xml = [];
    private bool $securePage = false;

    /**
     * SitemapService constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LocaleService $localeService,
    ) {
    }

    /**
     * Get XML data.
     *
     * @throws \Exception|InvalidArgumentException
     */
    public function execute(Website $website, ?string $locale = null, bool $noTrees = false, bool $force = false, bool $securePage = false): array
    {
        $this->forFront = !empty($locale);
        $this->securePage = $securePage;

        if ($force) {
            $this->coreLocator->em()->clear();
        }

        $this->setVars($website, $locale);

        if (!$force && !$this->configuration->seoStatus && !in_array($this->coreLocator->requestStack()->getCurrentRequest()->getClientIp(), $this->configuration->entity->getAllIPS())) {
            return $this->xml;
        }

        if (!$this->forFront) {
            $this->index();
        }
        $this->parseUrls();
        $this->internationalization();

        if ($this->forFront) {
            return $this->generateFront($noTrees);
        }

        return $this->xml;
    }

    /**
     * Set mains vars.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|ReflectionException|QueryException
     */
    public function setVars(?Website $website = null, ?string $locale = null): void
    {
        $this->host = $this->coreLocator->requestStack()->getCurrentRequest()->getHost();
        $this->locale = $locale ?: $this->coreLocator->requestStack()->getCurrentRequest()->getLocale();
        $this->website = $website ? WebsiteModel::fromEntity($website, $this->coreLocator) : $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($this->host);
        $this->configuration = $this->website->configuration;
        $this->urls = $this->setUrls();
        $this->localesWebsites = $this->localeService->getLocalesWebsites($this->website);
    }

    /**
     * Set Xml Index.
     *
     * @throws \Exception|InvalidArgumentException
     */
    private function index(): void
    {
        $pages = $this->coreLocator->em()->getRepository(Page::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('p.website = :website')
            ->andWhere('p.asIndex = :asIndex')
            ->setParameter('website', $this->website->entity)
            ->setParameter('asIndex', true)
            ->addSelect('u')
            ->getQuery()
            ->getResult();
        $page = !empty($pages[0]) ? $pages[0] : null;

        if ($page) {
            $interface = $this->coreLocator->interfaceHelper()->generate(Page::class);
            foreach ($this->localesWebsites as $locale => $domain) {
                if (!empty($this->localesWebsites[$locale])) {
                    $this->xml['pages'][$page->getId()][$locale]['url'] = $this->localesWebsites[$locale];
                }
                foreach ($page->getUrls() as $url) {
                    if ($url->getLocale() === $locale) {
                        $this->xml['pages'][$page->getId()][$locale]['update'] = $this->lastUpdatePage($page);
                        $this->xml['pages'][$page->getId()][$locale]['urlEntity'] = $url;
                    }
                }
                $model = ViewModel::fromEntity($page, $this->coreLocator, [
                    'disabledMedias' => true,
                    'disabledCategories' => true,
                    'disabledLayout' => true,
                    'disabledIntl' => $this->forFront
                ]);
                $this->xml['pages'][$page->getId()][$locale]['interface'] = $interface;
                $this->xml['pages'][$page->getId()][$locale]['entity'] = $model;
                $this->xml['pages'][$page->getId()][$locale]['isIndex'] = true;
                $this->xml['pages'][$page->getId()][$locale]['active'] = false;
                $this->xml['pages'][$page->getId()][$locale]['title'] = $this->forFront && $model->intl?->title ? $model->intl->title : $this->coreLocator->seoService()->execute($url, $model, null, true)['title'];
                $this->xml['pages'][$page->getId()][$locale]['isInfill'] = false;
            }
        }
    }

    /**
     * Set Xml Page.
     *
     * @throws \Exception|InvalidArgumentException
     */
    public function setPage(mixed $entity, mixed $url, array $interface = [])
    {
        $entityDb = $entity->entity;
        $isInfill = method_exists($entityDb, 'isInfill') ? $entityDb->isInfill() : false;
        $urlEntity = is_object($url) && property_exists($url, 'url') ? $url->url : $url;
        $code = $isInfill ? true : (is_object($urlEntity) ? $urlEntity->getCode() : $urlEntity['code']);
        if (!empty($code) && is_object($entity) && 'components.html.twig' !== $entity->template && 'error' !== $entity->slug) {
            $locale = is_object($urlEntity) ? $urlEntity->getLocale() : $urlEntity['locale'];
            $uri = $isInfill ? null : $this->coreLocator->router()->generate('front_index', ['url' => $code]);
            $title = $this->forFront && $entity->intl?->title ? $entity->intl->title : $this->coreLocator->seoService()->execute($urlEntity, $entity, null, true)['title'];
            $strUrl = $isInfill ? null : (!empty($this->localesWebsites[$locale]) ? $this->localesWebsites[$locale].$uri : null);
            $this->xml['pages'][$entity->id][$locale] = [
                'update' => $this->lastUpdatePage($entity),
                'uri' => $isInfill ? null : $uri,
                'url' => $strUrl,
                'active' => $this->coreLocator->request() && $strUrl === $this->coreLocator->request()->getUri(),
                'interface' => !empty($interface) ? $interface : ($url instanceof Url ? $url::getInterface() : []),
                'title' => $title ?: $entityDb->getAdminName(),
                'entity' => $entity,
                'urlEntity' => $urlEntity,
                'isInfill' => $isInfill,
            ];
            return $this->xml['pages'][$entity->id][$locale];
        }

        return false;
    }

    /**
     * Set all online Url.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function setUrls(): array
    {
        $urls = [];

        $metasData = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();
        foreach ($metasData as $metadata) {
            $classname = $metadata->getName();
            $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
            if ($baseEntity && method_exists($baseEntity, 'getUrls') && method_exists($baseEntity, 'getWebsite')) {
                $entities = $this->getEntities($classname, $baseEntity);
                foreach ($entities as $entity) {
                    $entityObj = $entity->entity;
                    if (($entity->online || (method_exists($entityObj, 'isInfill') && $entityObj->isInfill())) && !$entity->urlEntity->isHideInSitemap()) {
                        $urls[] = (object) ['url' => $entity->urlEntity, 'entityObj' => $entityObj, 'entity' => $entity, 'interface' => $entity->interface];
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * Set all entities.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function getEntities(string $classname, $baseEntity, array $entities = []): array
    {
        $queryBuilder = $this->coreLocator->em()->getRepository($classname)
            ->createQueryBuilder('e')
            ->leftJoin('e.website', 'w')
            ->leftJoin('e.urls', 'u')
            ->andWhere('e.website = :website')
            ->andWhere('u.locale = :locale')
            ->andWhere('u.archived = :archived')
            ->setParameter('website', $this->website->entity)
            ->setParameter('locale', $this->locale)
            ->setParameter('archived', false)
            ->addSelect('w')
            ->addSelect('u');

        if (method_exists($baseEntity, 'getIntls')) {
            $queryBuilder->leftJoin('e.intls', 'i')
                ->addSelect('i');
        }

        if (method_exists($baseEntity, 'getPublicationStart') && method_exists($baseEntity, 'getPublicationEnd')) {
            $queryBuilder->andWhere('e.publicationStart IS NULL OR e.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('e.publicationEnd IS NULL OR e.publicationEnd > CURRENT_TIMESTAMP()');
        }

        if (method_exists($baseEntity, 'getCategories')) {
            $queryBuilder->leftJoin('e.categories', 'categories')
                ->addSelect('categories');
        }

        $entitiesDb = $queryBuilder->getQuery()->getResult();
        $excludedToModel = ['disabledMedias' => true, 'disabledCategories' => true, 'disabledLayout' => (Page::class === $classname || !$this->forFront), 'disabledIntl' => !$this->forFront];
        foreach ($entitiesDb as $entity) {
            $entities[$entity->getId()] = ViewModel::fromEntity($entity, $this->coreLocator, $excludedToModel);
        }

        return $entities;
    }

    /**
     * Parse all Urls result.
     *
     * @throws \Exception|InvalidArgumentException
     */
    private function parseUrls(): void
    {
        $indexPagesCodes = $this->getIndexPages($this->urls);
        foreach ($this->urls as $url) {
            $urlEntity = $url->url;
            if ($urlEntity->isAsIndex() && !$urlEntity->isHideInSitemap() && !empty($this->localesWebsites[$urlEntity->getLocale()])) {
                $entity = $url->entity;
                $interface = $url->interface;
                if (!empty($interface['classname']) && Page::class === $interface['classname'] && !$entity->entity->isAsIndex()) {
                    $this->setPage($entity, $url, $interface);
                } elseif (!empty($interface['classname']) && Page::class !== $interface['classname']) {
                    $this->setAsCard($entity, $interface, $url, $indexPagesCodes);
                }
            }
        }
    }

    /**
     * Set Xml for entity has card.
     *
     * @throws \Exception|InvalidArgumentException
     */
    public function setAsCard(mixed $entity, array $interface, mixed $url, array $indexPagesCodes = []): ?array
    {
        $urlEntity = $url instanceof Url ? $url : $url->url;
        if ($urlEntity->getCode()) {
            $urlInfos = $this->coreLocator->seoService()->getAsCardUrl($urlEntity, $entity, $interface['classname'], true, $interface, $indexPagesCodes);
            if ($urlInfos) {
                $this->xml[$interface['name']][$entity->id][$urlEntity->getLocale()] = [
                    'update' => $this->getDate($entity),
                    'uri' => $urlInfos->uri,
                    'url' => $urlInfos->canonical,
                    'active' => $this->coreLocator->request() && $urlInfos->canonical === $this->coreLocator->request()->getUri(),
                    'interface' => !empty($interface) ? $interface : ($url instanceof Url ? $url::getInterface() : $url->interface),
                    'entity' => $entity,
                    'urlEntity' => $urlEntity,
                    'isInfill' => false,
                ];
                return $this->xml[$interface['name']][$entity->id][$urlEntity->getLocale()];
            }
        }

        return null;
    }

    /**
     * Get Index Pages.
     *
     * @throws NonUniqueResultException
     */
    public function getIndexPages(mixed $entities, array $interface = []): array
    {
        $entitiesToIndex = $indexPagesCodes = [];

        foreach ($entities as $entity) {
            $interface = empty($interface) ? $entity->interface : $interface;
            if (!empty($interface['listingClass'])) {
                $entity = is_object($entity) && property_exists($entity, 'entity') ? $entity->entity
                    : (is_array($entity) && isset($entity['object']) ? $entity['object'] : $entity);
                $entitiesToIndex[$interface['classname']][$interface['listingClass']][] = $entity;
            }
        }

        foreach ($entitiesToIndex as $classname => $group) {
            foreach ($group as $listingClassname => $entities) {
                $referEntity = !empty($entities[0]) ? $entities[0] : null;
                if ($referEntity) {
                    $indexPagesCodes[$classname] = $this->coreLocator->listingService()->indexesPages($referEntity, $this->coreLocator->requestStack()->getCurrentRequest()->getLocale(), $listingClassname, $classname, $entities);
                }
            }
        }

        return $indexPagesCodes;
    }

    /**
     * Get last update for entity with layout.
     *
     * @throws \Exception
     */
    private function lastUpdatePage(mixed $entity): ?string
    {
        $updateDate = $this->getDate($entity);

        return $updateDate->format('Y-m-d');
    }

    /**
     * Get Date.
     */
    private function getDate(mixed $entity): ?\DateTimeImmutable
    {
        $entity = $entity instanceof ViewModel ? $entity->entity : $entity;

        if (is_object($entity) && method_exists($entity, 'getUpdatedAt') && $entity->getUpdatedAt()
            || is_object($entity) && method_exists($entity, 'getCreatedAt') && $entity->getCreatedAt()) {
            return method_exists($entity, 'getUpdatedAt') && $entity->getUpdatedAt() ? $entity->getUpdatedAt()
                : (method_exists($entity, 'getCreatedAt') && $entity->getCreatedAt() ? $entity->getCreatedAt() : null);
        } elseif (is_object($entity) && method_exists($entity, 'getPublicationStart') && $entity->getPublicationStart()) {
            return $entity->getPublicationStart();
        } elseif (is_array($entity) && !empty($entity['updatedAt']) || is_array($entity) && !empty($entity['createdAt'])) {
            return !empty($entity['updatedAt']) ? $entity['updatedAt'] : $entity['createdAt'];
        }

        return new \DateTimeImmutable('01-01-1970');
    }

    /**
     * Format XML by Locales for locales alternate.
     */
    private function internationalization(): void
    {
        $xmlLocales = [];

        /* Generate all locales groups */
        foreach ($this->localesWebsites as $locale => $domain) {
            foreach ($this->xml as $urls) {
                foreach ($urls as $url) {
                    $xmlLocales[$locale][] = $url;
                }
            }
        }

        /* Remove urls groups if locale not exist */
        foreach ($xmlLocales as $mainLocale => $urlsGroups) {
            foreach ($urlsGroups as $key => $urls) {
                if (empty($urls[$mainLocale])) {
                    unset($xmlLocales[$mainLocale][$key]);
                }
            }
        }

        /* Order urls groups by locales */
        foreach ($xmlLocales as $mainLocale => $urlsGroups) {
            foreach ($urlsGroups as $key => $urls) {
                if (!empty($urls[$mainLocale])) {
                    $localeGroup = $xmlLocales[$mainLocale][$key][$mainLocale];
                    unset($xmlLocales[$mainLocale][$key][$mainLocale]);
                    $group = $xmlLocales[$mainLocale][$key];
                    $xmlLocales[$mainLocale][$key] = [$mainLocale => $localeGroup] + $group;
                }
            }
        }

        $defaultLocale = $this->configuration->locale;

        if (!empty($xmlLocales[$defaultLocale])) {
            $defaultLocaleXML = $xmlLocales[$defaultLocale];
            unset($xmlLocales[$defaultLocale]);
            $xmlLocales = [$defaultLocale => $defaultLocaleXML] + $xmlLocales;
        }

        $this->xml = $xmlLocales;
    }

    /**
     * Generate front.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|ReflectionException|QueryException
     */
    private function generateFront(bool $noTrees = false): array
    {
        $result = [];
        if (!empty($this->xml[$this->locale])) {
            /** Group by entities */
            $groups = [];
            foreach ($this->xml[$this->locale] as $urls) {
                foreach ($urls as $locale => $url) {
                    if ($locale === $this->locale) {
                        $groups[$url['interface']['name']][$url['entity']->id] = $url;
                    }
                }
            }
            $result = !$noTrees ? $this->getTree($groups) : $groups;
        }

        return $result;
    }

    /**
     * Get Tree of Entities.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|ReflectionException|QueryException
     */
    private function getTree(array $groups): array
    {
        $trees = $entitiesByParent = [];
        foreach ($groups as $group) {
            foreach ($group as $info) {
                $entity = $info['entity']->entity;
                if (is_object($entity) && !method_exists($entity, 'getParent')) {
                    $info['entityId'] = $info['entity']->id;
                    $position = !empty($trees[$info['interface']['name']]) ? $this->getPosition($trees[$info['interface']['name']], $info['entity']->position) : $info['entity']->position;
                    $trees[$info['interface']['name']][$position] = $info;
                    ksort($trees[$info['interface']['name']]);
                } elseif ($entity && !empty($info['urlEntity'])) {
                    $parent = method_exists($entity, 'getParent') && $entity->getParent() ? $entity->getParent()->getId() : 'main';
                    $info['entityId'] = $entity->getId();
                    $info['seo'] = $this->coreLocator->seoService()->execute($info['urlEntity'], $entity, null, true, null, ['disabledLayout' => true, 'disabledCategory' => true, 'disabledCategories' => true, 'disabledMedias' => true]);
                    $position = !empty($trees[$info['interface']['name']][$parent]) ? $this->getPosition($trees[$info['interface']['name']][$parent], $info['entity']->position) : $info['entity']->position;
                    $trees[$info['interface']['name']][$parent][$position] = $info;
                    $trees[$info['interface']['name']][$parent][$position]['title'] = !empty($info['seo']['titleH1']) ? $info['seo']['titleH1']
                        : (!empty($info['seo']['breadcrumb']) ? $info['seo']['breadcrumb']
                            : (!empty($info['seo']['title']) ? $info['seo']['title'] : $info['title']));
                    $entitiesByParent[$parent][$position] = $trees[$info['interface']['name']][$parent][$position];
                    ksort($trees[$info['interface']['name']][$parent]);
                    ksort($entitiesByParent[$parent]);
                }
            }
        }

        foreach ($trees as $categoryName => $groups) {
            foreach ($groups as $groupName => $items) {
                foreach ($items as $name => $value) {
                    if (!is_array($value) || !isset($value['entity']->entity)) {
                        continue;
                    }
                    $parentId = $value['entity']->entity->getId();
                    $children = $this->buildChildren($parentId, $entitiesByParent);
                    $trees[$categoryName][$groupName][$name]['children'] = $children;
                    if (!empty($children) && empty($trees[$categoryName][$groupName][$name]['url'])) {
                        $firstKey = array_key_first($children);
                        $trees[$categoryName][$groupName][$name]['url'] = $children[$firstKey]['url'] ?? null;
                    }
                }
            }
        }

        if (!empty($trees['page']['main'])) {
            foreach ($trees['page']['main'] as $keyPage => $page) {
                if ($page['isInfill'] && !$page['active'] && empty($page['children'])) {
                    unset($trees['page']['main'][$keyPage]);
                }
            }
        }

        return $trees;
    }

    /**
     * Build children recursively from entitiesByParent map.
     */
    private function buildChildren(int $parentId, array $entitiesByParent): array
    {
        $children = $entitiesByParent[$parentId] ?? [];
        foreach ($children as $k => $child) {
            $childId = $child['entity']->id; // expected structure
            $children[$k]['children'] = $this->buildChildren($childId, $entitiesByParent);
        }
        return $children;
    }

    /**
     * To get position.
     */
    private function getPosition(array $group, int $position): int
    {
        $newPosition = $position;
        if (isset($group[$position])) {
            $newPosition = $position + 1;
            if (isset($group[$newPosition])) {
                return $this->getPosition($group, $newPosition);
            }
        }

        return $newPosition;
    }
}
