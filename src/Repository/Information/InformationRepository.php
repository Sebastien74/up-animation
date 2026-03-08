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
     * Get Information as an array.
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
                ->enableResultCache(3600, 'info-array-'.$id)
                ->getArrayResult();
        }

        return ! empty($result[0]) ? $result[0] : [];
    }
    /**
     * Get entity object with full relations.
     */
    public function findObject(int $websiteId): ?Information
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.website', 'w')
            ->leftJoin('i.intls', 'intl')
            ->leftJoin('i.socialNetworks', 'sn')
            ->leftJoin('i.phones', 'p')
            ->leftJoin('i.emails', 'e')
            ->leftJoin('i.addresses', 'a')
            ->leftJoin('i.legals', 'l')
            ->leftJoin('i.scheduleDays', 's')
            ->leftJoin('s.occurrences', 'o')
            ->andWhere('w.id = :website')
            ->setParameter('website', $websiteId)
            ->addSelect('w')
            ->addSelect('intl')
            ->addSelect('sn')
            ->addSelect('p')
            ->addSelect('e')
            ->addSelect('a')
            ->addSelect('l')
            ->addSelect('s')
            ->addSelect('o')
            ->getQuery()
            ->enableResultCache(3600, 'info-object-'.$websiteId)
            ->getOneOrNullResult();
    }
}
