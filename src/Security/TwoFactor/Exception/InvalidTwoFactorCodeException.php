<?php

declare(strict_types=1);

namespace App\Security\TwoFactor\Exception;

/**
 * Thrown when a submitted TOTP code does not match the user's pending secret
 * during the 2FA enrolment flow.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class InvalidTwoFactorCodeException extends \DomainException
{
}
