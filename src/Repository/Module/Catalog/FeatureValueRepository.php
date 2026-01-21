<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\FeatureValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FeatureValueRepository.
 *
 * @extends ServiceEntityRepository<FeatureValue>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureValueRepository extends ServiceEntityRepository
{
    /**
     * FeatureValueRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FeatureValue::class);
    }

    /**
     * Find one by Feature, Value slugs and WebsiteModel.
     *
     * @throws NonUniqueResultException
     */
    public function findByFeatureAndValue(Website $website, string $slugFeature, string $slugValue): ?FeatureValue
    {
        return $this->createQueryBuilder('fv')
            ->leftJoin('fv.catalogfeature', 'cf')
            ->andWhere('fv.website = :website')
            ->andWhere('fv.slug = :valueSlug')
            ->andWhere('cf.slug = :featureSlug')
            ->setParameter('website', $website)
            ->setParameter('valueSlug', $slugValue)
            ->setParameter('featureSlug', $slugFeature)
            ->addSelect('cf')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by Catalog.
     */
    public function findByCatalog(Catalog $catalog): array
    {
        return $this->createQueryBuilder('fv')
            ->leftJoin('fv.catalogs', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $catalog->getId())
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save.
     */
    public function save(FeatureValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(FeatureValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
