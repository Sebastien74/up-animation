<?php

declare(strict_types=1);

namespace App\Repository\Module\Recruitment;

use App\Entity\Module\Recruitment\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ListingRepository.
 *
 * @extends ServiceEntityRepository<Listing>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ListingRepository extends ServiceEntityRepository
{
    /**
     * ListingRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Listing::class);
    }

    /**
     * Save.
     */
    public function save(Listing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Listing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
