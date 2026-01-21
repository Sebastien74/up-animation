<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\CustomIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CustomIntlRepository.
 *
 * @extends ServiceEntityRepository<CustomIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CustomIntlRepository extends ServiceEntityRepository
{
    /**
     * CustomIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CustomIntl::class);
    }

    public function save(CustomIntl $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CustomIntl $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
