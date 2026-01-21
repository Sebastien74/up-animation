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
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ProductRepository.
 *
 * @extends ServiceEntityRepository<Product>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProductRepository extends ServiceEntityRepository
{
    /**
     * ProductRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Product::class);
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
     * Find by Catalog[].
     *
     * @return array<Product>
     */
    public function findOnlineByCatalogs(Website $website, string $locale, array|PersistentCollection $catalogs = [], ?Listing $listing = null): array
    {
        $order = $listing instanceof Listing && $listing->getOrderBy() ? $listing->getOrderBy() : 'position';
        $sort = $listing instanceof Listing && $listing->getOrderSort() ? $listing->getOrderSort() : 'ASC';
        $queryBuilder = $this->optimizedQueryBuilder($locale, $website, $order, $sort)
            ->andWhere('u.archived = :archived')
            ->setParameter('archived', false);

        $catalogIds = [];
        foreach ($catalogs as $key => $catalog) {
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
        ?QueryBuilder $qb = null): QueryBuilder
    {
        $order = $order ?: 'publicationStart';
        $sort = $sort ?: 'DESC';

        $statement = $this->getOrCreateQueryBuilder($qb)
            ->innerJoin('p.catalog', 'catalog')
//            ->innerJoin('p.website', 'w')
            ->leftJoin('p.urls', 'u')
            ->leftJoin('p.categories', 'cat')
            ->leftJoin('p.subCategories', 'sc')
            ->leftJoin('p.intls', 'i')
//            ->leftJoin('p.mediaRelations', 'mr')
//            ->andWhere('p.website = :website')
            ->andWhere('u.locale = :locale')
//            ->andWhere('i.locale = :locale')
//            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
//            ->addSelect('w')
            ->addSelect('u')
            ->addSelect('cat')
            ->addSelect('sc')
//            ->addSelect('i')
//            ->addSelect('mr')
            ->addSelect('catalog');

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
