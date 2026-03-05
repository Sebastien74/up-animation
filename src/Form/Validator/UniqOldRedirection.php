<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * UniqOldRedirection.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class UniqOldRedirection extends Constraint
{
    protected string $message = '';
}
