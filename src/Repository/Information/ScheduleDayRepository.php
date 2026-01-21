<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\Information;
use App\Entity\Information\ScheduleDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ScheduleDayRepository.
 *
 * @extends ServiceEntityRepository<ScheduleDay>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ScheduleDayRepository extends ServiceEntityRepository
{
    /**
     * ScheduleDayRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ScheduleDay::class);
    }

    /**
     * Get ScheduleDay[] optimized query.
     */
    public function findByInformation(Information $information): array
    {
        return $this->createQueryBuilder('s')->select('s')
            ->leftJoin('s.occurrences', 'o')
            ->andWhere('s.information = :information')
            ->setParameter('information', $information)
            ->addSelect('o')
            ->getQuery()
            ->getResult();
    }
}
