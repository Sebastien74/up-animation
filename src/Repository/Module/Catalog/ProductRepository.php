<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Category;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\Listing;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Catalog\SubCategory;
use App\Entity\Media\Media;
use App\Model\Module\ProductModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;

/**
 * ProductRepository.
 *
 * @extends ServiceEntityRepository<Product>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProductRepository extends ServiceEntityRepository
{
    private array $cache = [];

    /**
     * ProductRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry, private readonly CoreLocatorInterface $coreLocator)
    {
        parent::__construct($this->registry, Product::class);
    }

    /**
     * Find by Id for admin.
     *
     * @throws NonUniqueResultException
     */
    public function findByOldUrl(string $url): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')
            ->andWhere('u.oldUrl = :oldUrl')
            ->setParameter('oldUrl', $url)
            ->addSelect('u')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by Id for admin.
     *
     * @throws NonUniqueResultException
     */
    public function findForAdmin(int $id): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.values', 'v')
            ->leftJoin('v.feature', 'vf')
            ->leftJoin('v.value', 'vv')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->addSelect('v')
            ->addSelect('vf')
            ->addSelect('vv')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all online by locale.
     *
     * @return array<Product>
     */
    public function findAllByLocale(Website $website, string $locale, bool $online = true, string $sort = 'ASC', string $order = 'publicationStart'): array
    {
        return $this->optimizedQueryBuilder($locale, $website, $order, $sort)
            ->andWhere('u.online = :online')
            ->setParameter('online', $online)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products by array of ID.
     *
     * @return array<Product>
     * @throws NonUniqueResultException
     */
    public function findByIds(Website $website, string $locale, array $ids = [], ?Listing $listing = null, bool $oneOrNullResult = false): mixed
    {
        $order = $listing instanceof Listing && $listing->getOrderBy() ? $listing->getOrderBy() : 'position';
        $sort = $listing instanceof Listing && $listing->getOrderSort() ? $listing->getOrderSort() : 'ASC';
        $method = $oneOrNullResult ? 'getOneOrNullResult' : 'getResult';

        return $this->optimizedQueryBuilder($locale, $website, $order, $sort)
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->$method();
    }

    /**
     * Prime the values collection.
     *
     * Hydrates Product.values (+ value, feature) for the whole set in one query so later
     * $product->getValues() iterations hit the already-initialized collection instead of
     * firing one SELECT per product (EXTRA_LAZY).
     *
     * @param array<Product> $products
     */
    public function primeValues(array $products): void
    {
        if (!$products) {
            return;
        }

        $this->createQueryBuilder('p')
            ->leftJoin('p.values', 'v')
            ->leftJoin('v.value', 'vv')
            ->leftJoin('v.feature', 'vf')
            ->andWhere('p IN (:products)')
            ->setParameter('products', $products)
            ->addSelect('v')
            ->addSelect('vv')
            ->addSelect('vf')
            ->getQuery()
            ->getResult();
    }

    /**
     * Prime every lazy collection accessed while building product view models.
     *
     * Products rendered on listing/teaser pages are loaded without their relations, so each
     * ProductModel::fromEntity() triggers one SELECT per collection per product (N+1). One
     * batch query per single collection (WHERE p IN (:ids)) warms the UnitOfWork without the
     * cartesian product a single multi-collection JOIN would cause.
     *
     * @param array<Product> $products
     */
    public function primeForRendering(array $products, string $locale): void
    {
        if (!$products) {
            return;
        }

        // FeatureValueProduct values (+ FeatureValue + Feature)
        $this->createQueryBuilder('p')
            ->leftJoin('p.values', 'v')->addSelect('v')
            ->leftJoin('v.value', 'vv')->addSelect('vv')
            ->leftJoin('v.feature', 'vf')->addSelect('vf')
            ->andWhere('p IN (:products)')->setParameter('products', $products)
            ->getQuery()->getResult();

        // SubCategories (+ parent Category accessed in the model)
        $this->createQueryBuilder('p')
            ->leftJoin('p.subCategories', 'sc')->addSelect('sc')
            ->leftJoin('sc.catalogcategory', 'scc')->addSelect('scc')
            ->andWhere('p IN (:products)')->setParameter('products', $products)
            ->getQuery()->getResult();

        // Translations, current locale only
        $this->createQueryBuilder('p')
            ->leftJoin('p.intls', 'i')->addSelect('i')
            ->andWhere('p IN (:products)')->setParameter('products', $products)
            ->andWhere('i.locale = :locale OR i.locale IS NULL')->setParameter('locale', $locale)
            ->getQuery()->getResult();

        // Urls
        $this->createQueryBuilder('p')
            ->leftJoin('p.urls', 'u')->addSelect('u')
            ->andWhere('p IN (:products)')->setParameter('products', $products)
            ->getQuery()->getResult();

        // Media relations (+ Media)
        $this->createQueryBuilder('p')
            ->leftJoin('p.mediaRelations', 'mr')->addSelect('mr')
            ->leftJoin('mr.media', 'm')->addSelect('m')
            ->andWhere('p IN (:products)')->setParameter('products', $products)
            ->getQuery()->getResult();

        // Media EAGER collections (thumbs + intls), batched once for the whole media set
        $medias = [];
        foreach ($products as $product) {
            foreach ($product->getMediaRelations() as $mediaRelation) {
                $media = $mediaRelation->getMedia();
                if ($media) {
                    $medias[$media->getId()] = $media;
                }
            }
        }
        if ($medias) {
            $this->getEntityManager()->getRepository(Media::class)->primeThumbsAndIntls(array_values($medias));
        }
    }

    /**
     * Find Newscast by url & locale.
     *
     * @throws NonUniqueResultException
     */
    public function findByUrlAndLocale(string $url, Website $website, string $locale, bool $preview = false): ?Product
    {
        return $this->optimizedQueryBuilder($locale, $website, null, null, $preview)
            ->andWhere('u.code = :code')
            ->setParameter('code', $url)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find like by text.
     *
     * @return array<Product>
     */
    public function findLikeInTitle(Website $website, string $locale, string $search, ?Listing $listing = null): array
    {
        $queryBuilder = $this->optimizedQueryBuilder($locale, $website)
//            ->leftJoin('p.intls', 'i')
            ->andWhere('i.title LIKE :search')
            ->setParameter(':search', '%'.$search.'%');

        if ($listing instanceof Listing) {
            if ($listing->isPromote()) {
                $queryBuilder->andWhere('p.promote = :promote')
                    ->setParameter('promote', true);
            }
            $catalogIds = [];
            foreach ($listing->getCatalogs() as $catalog) {
                $catalogIds[] = $catalog->getId();
            }
            if ($catalogIds) {
                $queryBuilder->andWhere('catalog.id IN (:catalogId)')
                    ->setParameter('catalogId', $catalogIds);
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }


    /**
     * Find Product[] in menus.
     *
     * @return array<Product>
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|QueryException|ReflectionException
     */
    public function findOnlineInMenus(Website $website, string $locale): array
    {
        $products = $this->optimizedQueryBuilder($locale, $website)
            ->andWhere('u.archived = :archived')
            ->andWhere('p.menu IS NOT NULL')
            ->setParameter('archived', false)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->enableResultCache(3600, 'products_in_menus_'.$website->getId().'_'.$locale)
            ->getResult();

        $menus = [];
        foreach ($products as $product) {
            $menus[$product->getMenu()][] = ProductModel::fromEntity($product, $this->coreLocator, [
                'disabledValues' => true,
                'disabledProducts' => true,
                'disabledLayout' => true,
                'disabledMedias' => true,
                'disabledCategories' => true,
                'disabledInfo' => true,
                'disabledCategory' => true
            ]);
        }

        foreach (['events', 'performances', 'animations', 'rentals'] as $slug) {
            if (empty($menus[$slug])) {
                $menus[$slug] = [];
            }
        }

        return $menus;
    }

    /**
     * Find by Catalog[].
     *
     * @return array<Product>
     */
    public function findOnlineByCatalogs(
        Website $website,
        string $locale,
        array|PersistentCollection $catalogs = [],
        ?Listing $listing = null,
        array $options = [],
    ): array {

        $asOnlyOneCatalog = $catalogs instanceof PersistentCollection && $catalogs->count() === 1 || count($catalogs) === 1;
        $firstCatalog = $asOnlyOneCatalog && $catalogs instanceof PersistentCollection ? $catalogs->first()
            : ($asOnlyOneCatalog ? $catalogs[array_key_first($catalogs)] : false);
        $listingSlug = $listing ? '-'.$listing->getSlug() : '';
        $keyCache = $firstCatalog ? 'onlineByCatalogs-'.$firstCatalog->getSlug().$listingSlug : 'onlineByCatalogs-'.$listingSlug;

        if ($keyCache && array_key_exists($keyCache, $this->cache)) {
            return $this->cache[$keyCache];
        }

        $order = $listing instanceof Listing && $listing->getOrderBy() ? $listing->getOrderBy() : 'position';
        $sort = $listing instanceof Listing && $listing->getOrderSort() ? $listing->getOrderSort() : 'ASC';
        $queryBuilder = $this->optimizedQueryBuilder($locale, $website, $order, $sort, false, null, $options)
            ->andWhere('u.archived = :archived')
            ->setParameter('archived', false);

        if ($listing instanceof Listing && $listing->isPromote()) {
            $queryBuilder->andWhere('p.promote = :promote')
                ->setParameter('promote', true);
        }

        $catalogIds = [];
        foreach ($catalogs as $catalog) {
            $catalogIds[] = $catalog->getId();
        }
        if ($catalogIds) {
            $queryBuilder->andWhere('catalog.id IN (:catalogId)')
                ->setParameter('catalogId', $catalogIds);
        }

        $products = $queryBuilder->getQuery()->getResult();

        if ('random' === $order) {
            shuffle($products);
        }

        foreach ($products as $key => $product) {
            /** @var Product $product */
            if (0 === $product->getUrls()->count() || !$product->getUrls()[0]->isOnline()) {
                unset($products[$key]);
            }
        }

        $this->cache[$keyCache] = $products;

        return $products;
    }

    /**
     * Find by Category[].
     */
    public function findOnlineByCategories(
        Website $website,
        string $locale,
        array|PersistentCollection|Collection $categories = [],
        mixed $catalog = null,
        bool $onlyProductsPromote = false,
        bool $onlyCategoriesPromote = false,
        array|PersistentCollection|Collection $subCategories = []): array
    {
        $queryBuilder = $this->optimizedQueryBuilder($locale, $website);

        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[] = $category->getId();
        }

        $subCategoryIds = [];
        foreach ($subCategories as $subCategory) {
            $subCategoryIds[] = $subCategory->getId();
        }

        if ($catalog instanceof Catalog) {
            $queryBuilder->andWhere('catalog.id = :catalogId')
                ->setParameter('catalogId', $catalog->getId());
        } elseif (is_iterable($catalog)) {
            $catalogIds = [];
            foreach ($catalog as $catalogDb) {
                $catalogIds[] = $catalogDb->getId();
            }
            if ($catalogIds) {
                $queryBuilder->andWhere('catalog.id IN (:catalogIds)')
                    ->andWhere('catalog.id IS NOT NULL')
                    ->setParameter('catalogIds', $catalogIds);
            }
        }

        if ($onlyCategoriesPromote) {
            $queryBuilder->andWhere('cat.promote = :promote')
                ->setParameter('promote', true);
        } elseif ($categoryIds) {
            $queryBuilder->andWhere('cat.id IN (:categoryIds)')
                ->andWhere('cat.id IS NOT NULL')
                ->setParameter('categoryIds', $categoryIds);
        }

        if ($subCategoryIds) {
            $queryBuilder->leftJoin('p.subCategories', 'subCat')
                ->addSelect('subCat')
                ->andWhere('subCat.id IN (:subCategoryIds)')
                ->andWhere('subCat.id IS NOT NULL')
                ->setParameter('subCategoryIds', $subCategoryIds);
        }

        $products = $queryBuilder->getQuery()->getResult();

        return $this->cleanResult($products, $locale, $onlyProductsPromote);
    }

    /**
     * Find by Category[].
     *
     * @return array<Product>
     */
    public function findOnlineByValues(
        Website $website,
        string $locale,
        array $values = [],
        string $condition = 'AND',
        bool $onlyProductsPromote = false): array
    {
        $queryBuilder = $this->optimizedQueryBuilder($locale, $website)
            ->join('p.values', 'v')
            ->join('v.value', 'vv');

        foreach ($values as $key => $value) {
            /** @var FeatureValue $value */
            $keyId = uniqid();
            $rowCondition = 'OR' === $condition && $key > 0 ? 'orWhere' : 'andWhere';
            $queryBuilder->$rowCondition('vv.id = :id'.$keyId)
                ->setParameter('id'.$keyId, $value->getId());
        }

        $products = $queryBuilder
//            ->addSelect('v')
//            ->addSelect('vv')
            ->getQuery()
            ->getResult();

        foreach ($products as $key => $product) {
            /** @var Product $product */
            if (0 === $product->getUrls()->count() || !$product->getUrls()[0]->isOnline()) {
                unset($products[$key]);
            }
        }

        return $this->cleanResult($products, $locale, $onlyProductsPromote);
    }

    /**
     * Find by value.
     *
     * @return array<Product>
     */
    public function findByValue(FeatureValue $featureValue): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.values', 'v')
            ->andWhere('v.value = :value')
            ->setParameter('value', $featureValue)
            ->addSelect('v')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by category.
     *
     * @return array<Product>
     */
    public function findByCategory(Category $category): array
    {
        $products = $this->createQueryBuilder('p')
            ->andWhere('p.mainCategory = :mainCategory')
            ->setParameter('mainCategory', $category)
            ->getQuery()
            ->getResult();

        if (!$products) {
            $products = $this->createQueryBuilder('p')
                ->leftJoin('p.categories', 'c')
                ->andWhere('c.id IN (:categoriesIds)')
                ->setParameter('categoriesIds', [$category->getId()])
                ->addSelect('c')
                ->getQuery()
                ->getResult();
        }

        return $products;
    }

    /**
     * Find by SubCategory.
     *
     * @return array<Product>
     */
    public function findBySubCategory(SubCategory $subCategory): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.subCategories', 'sc')
            ->andWhere('sc.id IN (:subCategoriesIds)')
            ->setParameter('subCategoriesIds', [$subCategory->getId()])
            ->addSelect('sc')
            ->getQuery()
            ->getResult();
    }

    /**
     * Optimized QueryBuilder.
     */
    private function optimizedQueryBuilder(
        string $locale,
        Website $website,
        ?string $order = null,
        ?string $sort = null,
        bool $preview = false,
        ?QueryBuilder $qb = null,
        array $options = []
    ): QueryBuilder {

        $order = $order ?: 'publicationStart';
        $sort = $sort ?: 'DESC';
        $disabledCategories = $options['disabledCategories'] ?? false;
        $disabledMedias = $options['disabledMedias'] ?? false;

        $statement = $this->getOrCreateQueryBuilder($qb)
            ->innerJoin('p.catalog', 'catalog')
            ->innerJoin('catalog.website', 'w')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.intls', 'i')
            ->andWhere('w.id = :websiteId')
            ->andWhere('u.locale = :locale')
            ->andWhere('i.locale = :locale')
            ->setParameter('locale', $locale)
            ->setParameter('websiteId', $website->getId())
            ->addSelect('u')
            ->addSelect('i')
            ->addSelect('catalog');

        if (!$disabledCategories) {
            $statement->leftJoin('p.categories', 'cat')
                ->leftJoin('p.subCategories', 'sc')
                ->addSelect('cat')
                ->addSelect('sc');
        }

        if (!$disabledMedias) {
            $statement->leftJoin('p.mediaRelations', 'mr')
                ->leftJoin('mr.media', 'm')
                ->addSelect('mr')
                ->addSelect('m');
        }

        if ('title' === $order) {
            $statement->orderBy('i.'.$order, $sort);
        } elseif ('random' !== $order) {
            $statement->orderBy('p.'.$order, $sort);
        }

        if (!$preview) {
            $statement->andWhere('p.publicationStart IS NULL OR p.publicationStart < CURRENT_TIMESTAMP()')
                ->andWhere('p.publicationEnd IS NULL OR p.publicationEnd > CURRENT_TIMESTAMP()')
                ->andWhere('p.publicationStart IS NOT NULL')
                ->andWhere('u.online = :online')
                ->setParameter('online', true);
        }

        return $statement;
    }

    /**
     * Main QueryBuilder.
     */
    private function getOrCreateQueryBuilder(?QueryBuilder $qb = null): QueryBuilder
    {
        return $qb ?: $this->createQueryBuilder('p');
    }

    /**
     * To clean result.
     */
    private function cleanResult(array $products, string $locale, bool $onlyProductsPromote = false): array
    {
        foreach ($products as $key => $product) {
            /** @var Product $product */
            $urlLocaleExiting = false;
            $unset = false;
            foreach ($product->getUrls() as $url) {
                if ($url->getLocale() === $locale) {
                    $urlLocaleExiting = true;
                    $unset = !$url->isOnline();
                    break;
                }
            }
            if (!$urlLocaleExiting || $unset || $onlyProductsPromote && !$product->isPromote()) {
                unset($products[$key]);
            }
        }

        return $products;
    }

    /**
     * Save.
     */
    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
