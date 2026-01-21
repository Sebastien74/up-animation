<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * ZipCode.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZipCode extends Constraint
{
    protected string $message = '';
    public array $departments = [];

    /**
     * ZipCode constructor.
     */
    public function __construct(array $options = [], ?string $message = null)
    {
        parent::__construct($options);

        $this->message = $message ?? $this->message;
        $this->departments = !empty($options['departments']) ? $options['departments'] : $this->departments;
    }
}
