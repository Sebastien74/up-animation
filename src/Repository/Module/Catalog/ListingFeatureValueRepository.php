<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\ListingFeatureValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ListingFeatureValueRepository.
 *
 * @extends ServiceEntityRepository<ListingFeatureValue>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ListingFeatureValueRepository extends ServiceEntityRepository
{
    /**
     * ListingFeatureValueRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ListingFeatureValue::class);
    }

    /**
     * Save.
     */
    public function save(ListingFeatureValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(ListingFeatureValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
