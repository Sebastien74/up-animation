<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * RegisteredUserFront.
 *
 * Email must match an existing front user for the current website.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class RegisteredUserFront extends Constraint
{
    public string $message = 'Aucun compte trouvé pour cet e-mail.';
}
