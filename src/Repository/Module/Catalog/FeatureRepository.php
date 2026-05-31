<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Catalog;
use App\Entity\Module\Catalog\Feature;
use App\Model\Core\WebsiteModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FeatureRepository.
 *
 * @extends ServiceEntityRepository<Feature>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureRepository extends ServiceEntityRepository
{
    /**
     * FeatureRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Feature::class);
    }

    /**
     * Prime the EAGER collections (intls, mediaRelations) and the values collection.
     *
     * Loaded per feature via separate SELECTs otherwise (EAGER not fetch-joinable through the
     * iterate warmup); one batch query per single collection warms the UnitOfWork instead.
     */
    public function primeWebsiteEager(Website $website): void
    {
        $id = $website->getId();
        $this->createQueryBuilder('f')->leftJoin('f.intls', 'fi')->addSelect('fi')
            ->andWhere('f.website = :w')->setParameter('w', $id)->getQuery()->getResult();
        $this->createQueryBuilder('f')->leftJoin('f.mediaRelations', 'fmr')->addSelect('fmr')
            ->andWhere('f.website = :w')->setParameter('w', $id)->getQuery()->getResult();
        $this->createQueryBuilder('f')->leftJoin('f.values', 'fv')->addSelect('fv')
            ->andWhere('f.website = :w')->setParameter('w', $id)->getQuery()->getResult();
    }

    /**
     * Find by Website iterate.
     */
    public function findAllByWebsiteIterate(WebsiteModel $websiteModel): \Generator
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.website = :website')
            ->setParameter('website', $websiteModel->entity->getId())
            ->getQuery()
            ->toIterable();
    }

    /**
     * Find by Catalog.
     */
    public function findByCatalog(Catalog $catalog): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.catalogs', 'c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $catalog->getId())
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by Catalog.
     */
    public function findByWebsiteOrderValue(Website $website, string $sort = 'position', string $order = 'ASC'): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.values', 'v')
            ->andWhere('f.id = :website')
            ->setParameter('website', $website)
            ->addSelect('v')
            ->orderBy('v.'.$sort, $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Save.
     */
    public function save(Feature $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Feature $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
