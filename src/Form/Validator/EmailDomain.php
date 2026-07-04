<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * EmailDomain.
 *
 * Check if e-mail domain has valid DNS records (MX)
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailDomain extends Constraint
{
    protected string $message = '';

    /**
     * EmailDomain constructor.
     */
    public function __construct(array $options = [], ?string $message = null)
    {
        parent::__construct($options);

        $this->message = $message ?? $this->message;
    }
}
