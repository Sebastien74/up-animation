<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Security\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * BackUserSessionDetector.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class BackUserSessionDetector
{
    private const string REQUIRED_ROLE = 'ROLE_ALLOWED_TO_SWITCH';

    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function getEligibleBackUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return in_array(self::REQUIRED_ROLE, $token->getRoleNames(), true) ? $user : null;
    }
}
