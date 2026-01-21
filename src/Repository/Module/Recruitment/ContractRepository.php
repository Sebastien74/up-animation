<?php

declare(strict_types=1);

namespace App\Repository\Module\Recruitment;

use App\Entity\Module\Recruitment\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ContractRepository.
 *
 * @extends ServiceEntityRepository<Contract>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ContractRepository extends ServiceEntityRepository
{
    /**
     * ContractRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Contract::class);
    }

    /**
     * Save.
     */
    public function save(Contract $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Contract $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
