<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use App\Entity\Security\UserFront;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Flips the email-based 2FA opt-in on a UserFront entity.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class FrontTwoFactorToggleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function toggle(UserFront $user): bool
    {
        $newState = !$user->isEmailAuthEnabled();
        $user->setEmailAuthEnabled($newState);
        $this->entityManager->flush();

        return $newState;
    }
}
