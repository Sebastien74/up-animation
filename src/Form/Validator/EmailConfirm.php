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

    public function __construct(
        ?string $fieldToCompare = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->fieldToCompare = $fieldToCompare ?? $this->fieldToCompare;
        $this->message = $message ?? $this->message;
    }
}
