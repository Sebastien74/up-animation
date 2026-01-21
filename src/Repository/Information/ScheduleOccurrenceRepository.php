<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\ScheduleOccurrence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ScheduleOccurrenceRepository.
 *
 * @extends ServiceEntityRepository<ScheduleOccurrence>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ScheduleOccurrenceRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ScheduleOccurrence::class);
    }

    public function add(ScheduleOccurrence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ScheduleOccurrence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
