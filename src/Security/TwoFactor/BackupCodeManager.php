<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Backup\BackupCodeManagerInterface;

/**
 * Validates and invalidates backup codes for admin users.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class BackupCodeManager implements BackupCodeManagerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isBackupCode(object $user, string $code): bool
    {
        return $user instanceof User && $user->isBackupCode($code);
    }

    public function invalidateBackupCode(object $user, string $code): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->invalidateBackupCode($code);
        $this->entityManager->flush();
    }
}
