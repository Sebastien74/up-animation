<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FeatureValueProductRepository.
 *
 * @extends ServiceEntityRepository<FeatureValueProduct>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureValueProductRepository extends ServiceEntityRepository
{
    /**
     * FeatureValueProductRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FeatureValueProduct::class);
    }

    /**
     * Find by Product and slug.
     *
     * @throws NonUniqueResultException
     */
    public function findByProductAndSlug(Product $product, string $featureSlug, ?string $valueSlug = null, int $limit = 0): FeatureValueProduct|array|null
    {
        $queryBuilder = $this->createQueryBuilder('fv')
            ->leftJoin('fv.product', 'p')
            ->leftJoin('fv.feature', 'f')
            ->andWhere('p.id = :id')
            ->andWhere('f.slug = :featureSlug')
            ->setParameter('id', $product->getId())
            ->setParameter('featureSlug', $featureSlug)
            ->addSelect('p')
            ->addSelect('f');

        if ($valueSlug) {
            $queryBuilder->leftJoin('fv.value', 'v')
                ->andWhere('v.slug = :valueSlug')
                ->setParameter('valueSlug', $valueSlug)
                ->addSelect('v');
        }

        $queryBuilder = $queryBuilder->getQuery();

        if ($limit > 1) {
            $queryBuilder->setMaxResults($limit);
        }

        return 1 === $limit ? $queryBuilder->getOneOrNullResult() : $queryBuilder->getResult();
    }

    /**
     * Find by Product ids.
     *
     * @throws NonUniqueResultException
     */
    public function findByProductIds(array $products = []): array
    {
        $ids = array_map(fn(Product $product) => $product->getId(), $products);

        return $this->createQueryBuilder('fv')
            ->leftJoin('fv.product', 'p')
            ->leftJoin('fv.feature', 'f')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->addSelect('p')
            ->addSelect('f')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save.
     */
    public function save(FeatureValueProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(FeatureValueProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
