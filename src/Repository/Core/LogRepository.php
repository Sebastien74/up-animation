<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Log;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LogRepository.
 *
 * @extends ServiceEntityRepository<Log>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LogRepository extends ServiceEntityRepository
{
    /**
     * LogRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Log::class);
    }

    /**
     * Get last entry.
     *
     * @throws NonUniqueResultException
     */
    public function findLast(): ?Log
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get unread log.
     *
     * @throws NonUniqueResultException
     */
    public function findUnread(): ?Log
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.asRead = :asRead')
            ->setParameter(':asRead', false)
            ->orderBy('l.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
