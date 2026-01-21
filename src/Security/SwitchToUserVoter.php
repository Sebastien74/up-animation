<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * SwitchToUserVoter.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SwitchToUserVoter extends Voter
{
    /**
     * SwitchToUserVoter constructor.
     */
    public function __construct(private readonly Security $security)
    {
    }

    protected function supports($attribute, $subject): bool
    {
        return 'CAN_SWITCH_USER' == $attribute && $subject instanceof UserInterface;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        /* If the user is anonymous or if the subject is not a user, do not grant access */
        if (!$user instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }

        /* Enabled switch for ROLE_INTERNAL */
        if ($this->security->isGranted('ROLE_INTERNAL')) {
            return true;
        }

        /* All switch only for ROLE_ALLOWED_TO_SWITCH */
        if ($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            return true;
        }

        return false;
    }
}
