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
     * Find Index.
     *
     * @throws NonUniqueResultException
     */
    public function findIndex(WebsiteModel $website, string $locale, bool $preview = false): ?Page
    {
        return $this->optimizedQueryBuilder($website, $locale, $preview)
            ->andWhere('p.asIndex = :asIndex')
            ->setParameter('asIndex', true)
            ->getQuery()
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
     * Find by URL code and locale.
     *
     * @throws NonUniqueResultException
     */
    public function findByUrlCodeAndLocale(WebsiteModel $website, string $urlCode, string $locale, bool $preview): Page|array|null
    {
        if (!empty($this->cache[$urlCode][$locale])) {
            return $this->cache[$urlCode][$locale];
        }

        $page = $this->optimizedQueryBuilder($website, $locale, $preview)
            ->andWhere('u.code = :code')
            ->setParameter('code', $urlCode)
            ->getQuery()
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

        if ($urlCode && $page) {
            $this->cache[$urlCode][$locale] = $page;
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
            ->getArrayResult();

        return $pages && 1 === count($pages) ? $pages[0] : null;
    }

    /**
     * Find by Action.
     */
    public function findOneByAction(
        mixed $website,
        string $locale,
        string $classname,
        int $filterId,
        ?string $slugAction = null): mixed
    {
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
            ->getResult();

        if (!$page && $slugAction) {
            $page = $queryBuilder->andWhere('a.slug = :slug')
                ->setParameter('slug', $slugAction)
                ->getQuery()
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
            ->getArrayResult();

        if (empty($pages) && $slugAction) {
            $pages = $queryBuilder->andWhere('a.slug = :slug')
                ->setParameter('slug', $slugAction)
                ->getQuery()
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
                ->getQuery()
                ->getOneOrNullResult();
            if ($result && !$result->getUrls()->isEmpty() && !empty($result->getUrls()[0]->getCode())) {
                foreach ($result->getUrls() as $url) {
                    /** @var Url $url */
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
     * Front optimized QueryBuilder.
     */
    public function optimizedQueryBuilder(WebsiteModel $website, string $locale, bool $offline = false): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->innerJoin('p.urls', 'u')
            ->innerJoin('p.website', 'w')
            ->innerJoin('p.layout', 'l')
            ->andWhere('u.website = :website')
            ->andWhere('u.locale = :locale')
            ->setParameter('locale', $locale)
            ->setParameter('website', $website->id)
            ->addSelect('u')
            ->addSelect('w')
            ->addSelect('l');

        if ($website->configuration->onlineStatus) {
            $queryBuilder->innerJoin('l.zones', 'z')
                ->innerJoin('z.cols', 'c')
                ->innerJoin('c.blocks', 'b')
                ->innerJoin('b.blockType', 'bt')
                ->addSelect('z')
                ->addSelect('c')
                ->addSelect('b')
                ->addSelect('bt');
        }

        if (!$offline) {
            $queryBuilder->andWhere('p.publicationStart IS NULL OR p.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('p.publicationEnd IS NULL OR p.publicationEnd > CURRENT_TIMESTAMP()')
                ->andWhere('u.online = :online')
                ->setParameter('online', true);
        }

        return $queryBuilder;
    }
}
