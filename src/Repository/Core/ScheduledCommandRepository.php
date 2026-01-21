<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\ScheduledCommand;
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
     * Find all enabled command ordered by priority.
     *
     * @return array<ScheduledCommand>
     */
    public function findEnabledCommand(): array
    {
        return $this->findBy(['disabled' => false, 'locked' => false], ['priority' => 'DESC']);
    }

    /**
     * Find all locked commands.
     *
     * @return array<ScheduledCommand>
     */
    public function findLockedCommand(): array
    {
        return $this->findBy(['disabled' => false, 'locked' => true], ['priority' => 'DESC']);
    }

    /**
     * Find all failed command.
     *
     * @return array<ScheduledCommand>
     */
    public function findFailedCommand(): array
    {
        return $this->createQueryBuilder('command')
            ->where('command.disabled = false')
            ->andWhere('command.lastReturnCode != 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<ScheduledCommand>
     */
    public function findFailedAndTimeoutCommands(bool|int $lockTimeout = false): array
    {
        /** Fist, get all failed commands (return != 0) */
        $failedCommands = $this->findFailedCommand();
        /* Then, si a timeout value is set, get locked commands and check timeout */
        if (false !== $lockTimeout) {
            $lockedCommands = $this->findLockedCommand();
            foreach ($lockedCommands as $lockedCommand) {
                $now = time();
                if ($lockedCommand->getLastExecution()->getTimestamp() + $lockTimeout < $now) {
                    $failedCommands[] = $lockedCommand;
                }
            }
        }

        return $failedCommands;
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
