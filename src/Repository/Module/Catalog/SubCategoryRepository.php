<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\SubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SubCategoryRepository.
 *
 * @extends ServiceEntityRepository<SubCategory>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SubCategoryRepository extends ServiceEntityRepository
{
    /**
     * SubCategoryRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SubCategory::class);
    }

    /**
     * Find by Slug & WebsiteModel.
     */
    public function findBySlugAndWebsite(string $slug, Website $website): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.catalogcategory', 'c')
            ->leftJoin('c.website', 'w')
            ->andWhere('s.slug = :slug')
            ->andWhere('c.website = :website')
            ->setParameter('slug', $slug)
            ->setParameter('website', $website)
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by Slug & WebsiteModel.
     */
    public function findByCategorySlugAndWebsite(string $slug, Website $website): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.catalogcategory', 'c')
            ->leftJoin('c.website', 'w')
            ->andWhere('c.slug = :slug')
            ->andWhere('c.website = :website')
            ->setParameter('slug', $slug)
            ->setParameter('website', $website)
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by WebsiteModel.
     */
    public function findByWebsite(Website $website): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.catalogcategory', 'c')
            ->leftJoin('c.website', 'w')
            ->andWhere('c.website = :website')
            ->setParameter('website', $website)
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by ids.
     */
    public function findByIds(Website $website, array $ids = []): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.catalogcategory', 'c')
            ->leftJoin('c.website', 'w')
            ->andWhere('s.id IN (:ids)')
            ->andWhere('c.website = :website')
            ->setParameter('ids', $ids)
            ->setParameter('website', $website)
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save.
     */
    public function save(SubCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(SubCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
