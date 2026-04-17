<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * EmailConfirm.
 *
 * @Annotation
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class EmailConfirm extends Constraint
{
    public string $message = 'Les adresses email ne correspondent pas.';
    public ?string $fieldToCompare = null;
}
