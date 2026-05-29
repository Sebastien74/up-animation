<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Security\UserFront;
use App\Form\Manager\Security\Front\ConfirmPasswordManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Behavioural test for the reset token expiry check (bug B).
 *
 * ConfirmPasswordManager::checkUser() must reject a request token older than
 * TOKEN_LIMIT (24h) and accept a fresh one. The EntityManager is mocked.
 */
final class ResetTokenExpiryTest extends TestCase
{
    public function testExpiredTokenIsRejected(): void
    {
        $user = $this->userRequestedAt(new \DateTimeImmutable('-25 hours', new \DateTimeZone('Europe/Paris')));

        self::assertNull($this->manager($user)->checkUser('req-token'));
        self::assertNull($user->getTokenRequest());
    }

    public function testFreshTokenIsAccepted(): void
    {
        $user = $this->userRequestedAt(new \DateTimeImmutable('-10 minutes', new \DateTimeZone('Europe/Paris')));

        self::assertSame($user, $this->manager($user)->checkUser('req-token'));
        self::assertSame('req-token', $user->getTokenRequest());
    }

    private function userRequestedAt(\DateTimeImmutable $requestedAt): UserFront
    {
        $user = new UserFront();
        $user->setToken('confirm-token');
        $user->setTokenRequest('req-token');
        $user->setTokenRequestDate($requestedAt);

        return $user;
    }

    private function manager(UserFront $user): ConfirmPasswordManager
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        return new ConfirmPasswordManager(
            $this->createMock(UserPasswordHasherInterface::class),
            $entityManager,
        );
    }
}
