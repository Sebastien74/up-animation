<?php

declare(strict_types=1);

namespace App\Security\TwoFactor\Exception;

/**
 * Thrown when the 2FA enrolment flow tries to confirm a setup but no pending
 * TOTP secret can be found in the user's session (typically: session expired,
 * cookie cleared, or direct POST without GET).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class MissingPendingSecretException extends \LogicException
{
}
