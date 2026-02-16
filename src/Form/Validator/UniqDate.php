<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * UniqDate.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqDate extends Constraint
{
    protected string $message = '';
}
