<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\ScheduledCommand;
use App\Entity\Core\Website;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ScheduledCommandRepository.
 *
 * @extends ServiceEntityRepository<ScheduledCommand>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ScheduledCommandRepository extends ServiceEntityRepository
{
    /**
     * ScheduledCommandRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ScheduledCommand::class);
    }

    /**
     * Find locked commands whose lock is older than the timeout (stale/orphaned locks).
     *
     * @return array<ScheduledCommand>
     */
    public function findStaleLockedCommands(int $lockTimeout): array
    {
        $threshold = new \DateTimeImmutable(sprintf('-%d seconds', $lockTimeout), new \DateTimeZone('Europe/Paris'));

        return $this->createQueryBuilder('command')
            ->where('command.locked = true')
            ->andWhere('command.lastExecution < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Lightweight projection for the dashboard report (no entity hydration).
     *
     * @return array<int, array{name: ?string, command: string, cronExpression: string, active: bool, locked: bool, lastExecution: ?\DateTimeInterface, lastReturnCode: ?int}>
     */
    public function findReportRows(Website $website): array
    {
        return $this->createQueryBuilder('sc')
            ->select(
                'sc.adminName AS name',
                'sc.command AS command',
                'sc.cronExpression AS cronExpression',
                'sc.active AS active',
                'sc.locked AS locked',
                'sc.lastExecution AS lastExecution',
                'sc.lastReturnCode AS lastReturnCode',
            )
            ->where('sc.website = :website')
            ->setParameter('website', $website)
            ->orderBy('sc.priority', 'DESC')
            ->addOrderBy('sc.adminName', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransactionRequiredException
     */
    public function getNotLockedCommand(ScheduledCommand $command): mixed
    {
        $query = $this->createQueryBuilder('command')
            ->where('command.locked = false')
            ->andWhere('command.id = :id')
            ->setParameter('id', $command->getId())
            ->getQuery();
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        return $query->getOneOrNullResult();
    }
}
