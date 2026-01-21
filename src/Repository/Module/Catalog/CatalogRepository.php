<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\Catalog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CatalogRepository.
 *
 * @extends ServiceEntityRepository<Catalog>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CatalogRepository extends ServiceEntityRepository
{
    /**
     * CatalogRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Catalog::class);
    }

    /**
     * Save.
     */
    public function save(Catalog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Catalog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
