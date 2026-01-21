<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * SmartListValidator.
 *
 * Check if content contain <li></li>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SmartListValidator extends ConstraintValidator
{
    /**
     * SmartListValidator constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value && !str_contains($value, '<li>')) {
            $this->context->buildViolation($this->coreLocator->translator()->trans('Pour afficher vos messages vous devez faire une liste à puces.', [], 'validators_cms'))
                ->addViolation();
        }
    }
}