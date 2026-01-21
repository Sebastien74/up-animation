<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * UniqOldRedirection.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqOldRedirection extends Constraint
{
    protected string $message = '';
}
