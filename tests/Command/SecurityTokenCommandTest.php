<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\SecurityTokenCommand;
use App\Entity\Security\UserFront;
use App\Service\Core\CronSchedulerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Behavioural test for security:reset:token (bug A).
 *
 * Confirms the command nulls a request token once its companion date is older
 * than the lifetime, and keeps a fresh one. The EntityManager is mocked, so no
 * database is involved.
 */
final class SecurityTokenCommandTest extends TestCase
{
    public function testExpiredRequestTokenIsCleared(): void
    {
        $user = $this->userRequestedAt(new \DateTimeImmutable('-25 hours', new \DateTimeZone('Europe/Paris')));

        $this->runCommandReturning($user);

        self::assertNull($user->getTokenRequest());
        self::assertNull($user->getTokenRequestDate());
    }

    public function testFreshRequestTokenIsKept(): void
    {
        $user = $this->userRequestedAt(new \DateTimeImmutable('-1 hour', new \DateTimeZone('Europe/Paris')));

        $this->runCommandReturning($user);

        self::assertSame('fresh-token', $user->getTokenRequest());
    }

    private function userRequestedAt(\DateTimeImmutable $requestedAt): UserFront
    {
        $user = new UserFront();
        $user->setTokenRequest('fresh-token');
        $user->setTokenRequestDate($requestedAt);

        return $user;
    }

    private function runCommandReturning(UserFront $user): void
    {
        $query = $this->createMock(Query::class);
        // First call covers User::class (none), second covers UserFront::class.
        $query->method('getResult')->willReturnOnConsecutiveCalls([], [$user]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $command = new SecurityTokenCommand($entityManager, $this->createMock(CronSchedulerService::class));
        $application = new Application();
        $application->add($command);

        (new CommandTester($application->find('security:reset:token')))->execute([]);
    }
}
