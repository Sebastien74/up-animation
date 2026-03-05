<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * UniqUserEmail.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class UniqUserEmail extends Constraint
{
    protected string $message = '';

    protected User|UserFront|null $user = null;
}
