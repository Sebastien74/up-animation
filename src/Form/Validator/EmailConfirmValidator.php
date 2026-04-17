<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EmailConfirmValidator.
 *
 * Check if the email matches the confirmation field
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailConfirmValidator extends ConstraintValidator
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var EmailConfirm $constraint */
        if (!$constraint->fieldToCompare) {
            return;
        }

        $formData = $this->context->getRoot()->getData();
        $emailToCompare = $formData[$constraint->fieldToCompare] ?? null;

        if ($value !== $emailToCompare) {
            $message = $this->translator->trans($constraint->message, [], 'validators');
            $this->context->buildViolation($message)->addViolation();
        }
    }
}
