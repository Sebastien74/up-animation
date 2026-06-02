<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\Core\WebsiteModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageRepository.
 *
 * @extends ServiceEntityRepository<Page>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageRepository extends ServiceEntityRepository
{
    private array $cache = [];

    /**
     * PageRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Page::class);
    }

    /**
     * Light projection used for HTTP cache validation (ETag/Last-Modified) before full hydration.
     *
     * @throws NonUniqueResultException
     */
    public function findCacheStampByUrlAndLocale(WebsiteModel $website, ?string $urlCode, string $locale, bool $preview = false): ?array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id AS pageId', 'p.updatedAt AS pageUpdatedAt', 'p.createdAt AS pageCreatedAt', 'p.secure AS pageSecure')
            ->addSelect('u.id AS urlId', 'u.updatedAt AS urlUpdatedAt')
            ->innerJoin('p.urls', 'u')
            ->andWhere('u.website = :website')
            ->andWhere('u.locale = :locale')
            ->andWhere('u.archived = :archived')
            ->setParameter('website', $website->id)
            ->setParameter('locale', $locale)
            ->setParameter('archived', false);

        if (!$preview) {
            $qb->andWhere('p.publicationStart IS NULL OR p.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('p.publicationEnd IS NULL OR p.publicationEnd > CURRENT_TIMESTAMP()')
                ->andWhere('u.online = :online')
                ->setParameter('online', true);
        }

        if (null === $urlCode || '' === $urlCode) {
            $qb->andWhere('p.asIndex = :asIndex')->setParameter('asIndex', true);
            $cacheKey = 'page-stamp-'.$website->id.'-index-'.$locale.'-'.(int) $preview;
        } else {
            $qb->andWhere('u.code = :code')->setParameter('code', $urlCode);
            $cacheKey = 'page-stamp-'.$website->id.'-'.$urlCode.'-'.$locale.'-'.(int) $preview;
        }

        return $qb->setMaxResults(1)
            ->getQuery()
            ->enableResultCache(3600, $cacheKey)
            ->getOneOrNullResult();
    }

    /**
     * Find Index.
     *
     * @throws NonUniqueResultException
     */
    public function findIndex(WebsiteModel $website, string $locale, bool $preview = false): ?Page
    {
        $cacheKey = 'page-index-id-'.$website->id.'-'.$locale.'-'.(int) $preview;
        return $this->optimizedQueryBuilder($website, $locale, $preview)
            ->andWhere('p.asIndex = :asIndex')
            ->setParameter('asIndex', true)
            ->getQuery()
            ->enableResultCache(3600, 'page-index-'.$website->id.'-'.$locale)
            ->getOneOrNullResult();
    }

    /**
     * Find for Tree position.
     *
     * @return array<Page>
     */
    public function findForTreePosition(Website $website, Page $page): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('u.archived = :archived')
            ->andWhere('p.deletable = :deletable')
            ->andWhere('p.website = :website')
            ->setParameter('archived', false)
            ->setParameter('deletable', true)
            ->setParameter('website', $website)
            ->addSelect('u');

        if (!$page->getParent()) {
            $queryBuilder->andWhere('p.parent IS NULL');
        } else {
            $queryBuilder->andWhere('p.parent = :parent')
                ->setParameter('parent', $page->getParent());
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find by old URL.
     */
    public function findByOldUrl(string $oldUrl): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('u.oldUrl = :oldUrl')
            ->setParameter('oldUrl', $oldUrl)
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by URL code and locale.
     *
     * @throws NonUniqueResultException
     */
    public function findByUrlCodeAndLocale(WebsiteModel $website, string $urlCode, string $locale, bool $preview): Page|array|null
    {
        if (!empty($this->cache[$urlCode][$locale][$website->id])) {
            return $this->cache[$urlCode][$locale][$website->id];
        }

        $page = $this->optimizedQueryBuilder($website, $locale, $preview)
            ->andWhere('u.code = :code')
            ->andWhere('u.archived = :archived')
            ->setParameter('code', $urlCode)
            ->setParameter('archived', false)
            ->getQuery()
            ->enableResultCache(3600, 'page-'.$website->id.'-'.$urlCode.'-'.$locale)
            ->getOneOrNullResult();

        if ($page instanceof Page && $page->isInFill() && $page->getPages()->count() > 0) {
            foreach ($page->getPages() as $page) {
                foreach ($page->getUrls() as $url) {
                    if ($url->getLocale() === $locale && $url->isOnline()) {
                        return ['redirection' => $url->getCode()];
                    }
                }
            }
        }

        if ($urlCode) {
            $this->cache[$urlCode][$locale][$website->id] = $page;
        }

        return $page;
    }

    /**
     * Find by URL ID and locale.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByUrlIdAndLocale(int $urlId, string $locale): ?Page
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.urls', 'u')
            ->andWhere('u.id = :id')
            ->andWhere('u.locale = :locale')
            ->setParameter('id', $urlId)
            ->setParameter('locale', $locale)
            ->addSelect('u')
            ->getQuery()
            ->enableResultCache(3600, 'page_url_id_'.$urlId.'_'.$locale)
            ->getOneOrNullResult();
    }

    /**
     * Find by URL code and locale.
     */
    public function findCookiesPage(WebsiteModel $website, string $locale): ?array
    {
        $pages = $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('u.website', 'w')
            ->leftJoin('w.configuration', 'c')
            ->leftJoin('c.domains', 'd')
            ->andWhere('p.website = :website')
            ->andWhere('u.locale = :locale')
            ->andWhere('u.code LIKE :code')
            ->setParameter('code', '%cookies%')
            ->setParameter('locale', $locale)
            ->setParameter('website', $website->id)
            ->addSelect('u')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('d')
            ->getQuery()
            ->enableResultCache(3600, 'cookies_page_'.$website->id.'_'.$locale)
            ->getArrayResult();

        return $pages && 1 === count($pages) ? $pages[0] : null;
    }

    /**
     * Find all by Action.
     */
    public function findAllByAction(
        mixed $website,
        string $locale,
        string $classname,
        array $filterIds
    ): array {

        $websiteId = $website instanceof Website ? $website->getId() : $website['id'];

        if (array_key_exists('findAllByAction', $this->cache) && array_key_exists($classname, $this->cache['findAllByAction'])) {
            return $this->cache['findAllByAction'][$classname];
        }

        $pages = $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.website', 'w')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.action', 'a')
            ->leftJoin('b.actionIntls', 'ai')
            ->andWhere('p.website = :website')
            ->andWhere('u.locale = :locale')
            ->andWhere('a.entity = :entity')
            ->andWhere('ai.actionFilter IN (:actionFilters)')
            ->andWhere('ai.locale = :locale')
            ->setParameter('locale', $locale)
            ->setParameter('website', $websiteId)
            ->setParameter('entity', $classname)
            ->setParameter('actionFilters', $filterIds)
            ->addSelect('u', 'l', 'z', 'c', 'b', 'a', 'ai')
            ->addSelect('ai.actionFilter AS actionFilter')
            ->getQuery()
            ->enableResultCache(3600, 'pages_action_'.md5($classname.'_'.implode('_', $filterIds).'_'.$locale.'_'.$websiteId))
            ->getResult();

        $result = [];
        foreach ($pages as $page) {
            $result[$page['actionFilter']] = $page[0];
        }

        $this->cache['findAllByAction'][$classname] = $result;

        return $result;
    }

    /**
     * Find all pages by action across multiple locales (single query, deduplicated).
     *
     * @param array<string> $locales
     *
     * @return array<Page>
     */
    public function findAllByActionForLocales(
        mixed $website,
        array $locales,
        string $classname,
        array $filterIds
    ): array {
        if (!$locales || !$filterIds) {
            return [];
        }

        $websiteId = $website instanceof Website ? $website->getId() : $website['id'];
        $sortedLocales = $locales;
        sort($sortedLocales);
        $cacheKey = 'pages_action_locales_'.md5($classname.'_'.implode('_', $filterIds).'_'.implode(',', $sortedLocales).'_'.$websiteId);

        $rows = $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.action', 'a')
            ->leftJoin('b.actionIntls', 'ai')
            ->andWhere('p.website = :website')
            ->andWhere('u.locale IN (:locales)')
            ->andWhere('a.entity = :entity')
            ->andWhere('ai.actionFilter IN (:actionFilters)')
            ->andWhere('ai.locale IN (:locales)')
            ->setParameter('locales', $sortedLocales)
            ->setParameter('website', $websiteId)
            ->setParameter('entity', $classname)
            ->setParameter('actionFilters', $filterIds)
            ->addSelect('u', 'l', 'z', 'c', 'b', 'a', 'ai')
            ->getQuery()
            ->enableResultCache(3600, $cacheKey)
            ->getResult();

        $result = [];
        foreach ($rows as $page) {
            if ($page instanceof Page) {
                $result[$page->getId()] = $page;
            }
        }

        return array_values($result);
    }

    /**
     * Pages grouped by the referenced module entity id, for one locale (single query).
     *
     * @return array<int, array<Page>>
     */
    public function findPagesGroupedByActionFilter(
        mixed $website,
        string $locale,
        string $classname,
        array $filterIds
    ): array {
        if (!$filterIds) {
            return [];
        }

        $websiteId = $website instanceof Website ? $website->getId() : (is_array($website) ? $website['id'] : (int) $website);

        $rows = $this->createQueryBuilder('p')
            ->select('p', 'u', 'ai.actionFilter AS actionFilter')
            ->leftJoin('p.urls', 'u', 'WITH', 'u.locale = :locale')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.action', 'a')
            ->leftJoin('b.actionIntls', 'ai')
            ->andWhere('p.website = :website')
            ->andWhere('a.entity = :entity')
            ->andWhere('ai.actionFilter IN (:actionFilters)')
            ->andWhere('ai.locale = :locale')
            ->setParameter('locale', $locale)
            ->setParameter('website', $websiteId)
            ->setParameter('entity', $classname)
            ->setParameter('actionFilters', $filterIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $page = $row[0];
            $result[$row['actionFilter']][$page->getId()] = $page;
        }

        return array_map(static fn (array $pages): array => array_values($pages), $result);
    }

    /**
     * Find pages indexes by url code.
     */
    public function findPagesIndexByUrl(array $ids, string $classname, string $locale): array
    {
        if (array_key_exists('findPagesIndexByUrl', $this->cache) && array_key_exists($classname, $this->cache['findPagesIndexByUrl'])) {
            return $this->cache['findPagesIndexByUrl'][$classname];
        }

        $pages = $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('p.id IN (:ids)')
            ->andWhere('u.locale = :locale')
            ->setParameter('locale', $locale)
            ->setParameter('ids', $ids)
            ->addSelect('u')
            ->addSelect('u.code AS urlCode')
            ->getQuery()
            ->enableResultCache(3600, 'pages_index_url_'.md5(implode('_', $ids).'_'.$locale))
            ->getResult();

        $result = [];
        foreach ($pages as $page) {
            $result[$page[0]->getId()] = $page['urlCode'];
        }

        $this->cache['findPagesIndexByUrl'][$classname] = $result;

        return $result;
    }

    /**
     * Find by Action.
     */
    public function findOneByAction(
        mixed $website,
        string $locale,
        string $classname,
        int $filterId,
        ?string $slugAction = null
    ): mixed {

        $websiteId = $website instanceof Website ? $website->getId() : $website['id'];

        if (array_key_exists($filterId, $this->cache[$classname][$websiteId][$locale] ?? [])) {
            return $this->cache[$classname][$websiteId][$locale][$filterId];
        }

        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.website', 'w')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.action', 'a')
            ->leftJoin('b.actionIntls', 'ai')
            ->andWhere('p.website = :website')
            ->andWhere('u.locale = :locale')
            ->setParameter('locale', $locale)
            ->setParameter('website', $websiteId)
            ->addSelect('u');

        $page = $queryBuilder
            ->andWhere('a.entity = :entity')
            ->andWhere('ai.actionFilter = :actionFilter')
            ->setParameter('entity', $classname)
            ->setParameter('actionFilter', $filterId)
            ->setMaxResults(1)
            ->getQuery()
            ->enableResultCache(3600, 'page_action_'.md5($websiteId.'_'.$locale.'_'.$classname.'_'.$filterId))
            ->getResult();

        if (!$page && $slugAction) {
            $page = $queryBuilder->andWhere('a.slug = :slug')
                ->setParameter('slug', $slugAction)
                ->getQuery()
                ->enableResultCache(3600, 'page_action_slug_'.md5($websiteId.'_'.$locale.'_'.$classname.'_'.$slugAction))
                ->getResult();
        }

        $this->cache[$classname][$websiteId][$locale][$filterId] = !empty($page[0]) ? $page[0] : null;

        return $this->cache[$classname][$websiteId][$locale][$filterId];
    }

    /**
     * Find by Action.
     */
    public function findByAction(
        mixed $website,
        string $locale,
        string $classname,
        array $filterIds,
        ?string $slugAction = null): mixed
    {
        $websiteId = $website instanceof Website ? $website->getId() : $website['id'];

        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.website', 'w')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.action', 'a')
            ->leftJoin('b.actionIntls', 'ai')
            ->andWhere('p.website = :website')
            ->andWhere('u.locale = :locale')
            ->setParameter('locale', $locale)
            ->setParameter('website', $websiteId)
            ->addSelect('u', 'l', 'z', 'c', 'b', 'a', 'ai');

        $pages = $queryBuilder
            ->andWhere('a.entity = :entity')
            ->andWhere('ai.actionFilter IN (:actionFilters)')
            ->andWhere('ai.locale = :locale')
            ->setParameter('entity', $classname)
            ->setParameter('actionFilters', $filterIds)
            ->setMaxResults(1)
            ->getQuery()
            ->enableResultCache(3600, 'pages_action_ids_'.md5($websiteId.'_'.$locale.'_'.$classname.'_'.implode('_', $filterIds)))
            ->getArrayResult();

        if (empty($pages) && $slugAction) {
            $pages = $queryBuilder->andWhere('a.slug = :slug')
                ->setParameter('slug', $slugAction)
                ->getQuery()
                ->enableResultCache(3600, 'pages_action_slug_'.md5($websiteId.'_'.$locale.'_'.$classname.'_'.$slugAction))
                ->getArrayResult();
        }

        return $pages;
    }

    /**
     * Find by Action.
     */
    public function findByLayoutAndAction(
        Layout $layout,
        string $classname,
    ): ?Page {

        $result = $this->createQueryBuilder('p')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.action', 'a')
            ->andWhere('l.id = :layoutId')
            ->andWhere('a.entity = :entity')
            ->setParameter('layoutId', $layout->getId())
            ->setParameter('entity', $classname)
            ->getQuery()
            ->getResult();

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Find by WebsiteModel.
     *
     * @return array<Page>
     */
    public function findByWebsite(Website $website): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.website = :website')
            ->setParameter('website', $website)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by WebsiteModel.
     *
     * @return array<Page>
     */
    public function findByWebsiteNotArchived(Website $website): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('p.website = :website')
            ->andWhere('u.archived = :archived')
            ->setParameter('website', $website)
            ->setParameter('archived', false)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find locale Url by Page.
     *
     * @throws NonUniqueResultException
     */
    public function findOneUrlByPageAndLocale(string $locale, ?Page $page = null): ?Url
    {
        if ($page) {
            $result = $this->createQueryBuilder('p')
                ->leftJoin('p.urls', 'u')
                ->andWhere('p.id = :id')
                ->andWhere('u.locale = :locale')
                ->setParameter('id', $page->getId())
                ->setParameter('locale', $locale)
                ->addSelect('u')
                ->getQuery()
                ->enableResultCache(3600, 'page-url-'.md5($page->getId().'_'.$locale))
                ->getOneOrNullResult();
            if ($result && !$result->getUrls()->isEmpty()) {
                foreach ($result->getUrls() as $url) {
                    if ($url->getLocale() === $locale && $url->isOnline() && $url->getCode()) {
                        return $url;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Find by Block.
     *
     * @throws NonUniqueResultException
     */
    public function findByBlock(Block $block): ?Page
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->andWhere('b.id = :id')
            ->setParameter('id', $block->getId())
            ->addSelect('l')
            ->addSelect('z')
            ->addSelect('c')
            ->addSelect('b')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by parent Page.
     *
     * @return array<Page>
     */
    public function findOnlineAndLocaleByParent(Page $page, string $locale, bool $sameLevel): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('p.parent = :parent')
            ->andWhere('u.online = :online')
            ->andWhere('u.locale = :locale')
            ->setParameter('parent', $sameLevel ? $page->getParent() : $page)
            ->setParameter('online', true)
            ->setParameter('locale', $locale)
            ->addSelect('u')
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Front-optimized QueryBuilder.
     */
    public function optimizedQueryBuilder(WebsiteModel $website, string $locale, bool $preview = false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.urls', 'u')
            ->innerJoin('p.website', 'w')
            ->leftJoin('p.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.blockType', 'bt')
            ->leftJoin('b.action', 'ba')
            ->leftJoin('b.intls', 'bi', 'WITH', 'bi.locale = :locale')
            ->leftJoin('b.actionIntls', 'bai', 'WITH', 'bai.locale = :locale')
            ->leftJoin('b.mediaRelations', 'bmr')
            ->leftJoin('bmr.media', 'bmrm')
            ->leftJoin('bmr.intl', 'bmri')
            ->leftJoin('bmrm.thumbs', 'bmrmt')
            ->leftJoin('bmrm.intls', 'bmrmi')
            ->leftJoin('b.fieldConfiguration', 'bfc')
            ->leftJoin('p.intls', 'pi', 'WITH', 'pi.locale = :locale')
            ->leftJoin('p.mediaRelations', 'pmr')
            ->leftJoin('pmr.media', 'pmrm')
            ->leftJoin('pmr.intl', 'pmri')
            ->leftJoin('pmrm.thumbs', 'pmrmt')
            ->leftJoin('pmrm.intls', 'pmrmi')
            ->andWhere('u.website = :website')
            ->andWhere('u.locale = :locale')
            ->setParameter('website', $website->id)
            ->setParameter('locale', $locale)
            ->addSelect('u', 'w', 'l', 'z', 'c', 'b', 'bt', 'ba', 'bi', 'bai', 'bmr', 'bfc', 'pi')
            ->addSelect('bmrm', 'bmri', 'bmrmt', 'bmrmi', 'pmr', 'pmrm', 'pmri', 'pmrmt', 'pmrmi');

        if (!$preview) {
            $qb->andWhere('p.publicationStart IS NULL OR p.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('p.publicationEnd IS NULL OR p.publicationEnd > CURRENT_TIMESTAMP()')
                ->andWhere('u.online = :online')
                ->setParameter('online', true);
        }

        return $qb;
    }
}
