<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\Information;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * InformationRepository.
 *
 * @extends ServiceEntityRepository<Information>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InformationRepository extends ServiceEntityRepository
{
    /**
     * InformationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Information::class);
    }

    /**
     * Get Information as array.
     */
    public function findArray(?int $id = null): array
    {
        if ($id) {
            $result = $this->createQueryBuilder('i')
                ->leftJoin('i.intls', 'ii')
                ->leftJoin('i.emails', 'ie')
                ->leftJoin('i.addresses', 'ia')
                ->leftJoin('ia.phones', 'iap')
                ->leftJoin('ia.emails', 'iae')
                ->leftJoin('i.phones', 'ip')
                ->leftJoin('i.website', 'iw')
                ->andWhere('i.id = :id')
                ->setParameter('id', $id)
                ->addSelect('ii')
                ->addSelect('ie')
                ->addSelect('ia')
                ->addSelect('iap')
                ->addSelect('iae')
                ->addSelect('ip')
                ->addSelect('iw')
                ->getQuery()
                ->getArrayResult();
        }

        return ! empty($result[0]) ? $result[0] : [];
    }
}
