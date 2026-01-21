<?php

declare(strict_types=1);

namespace App\Form\Validator;

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MobileValidator.
 *
 * Check if is valid mobile phone
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MobileValidator extends ConstraintValidator
{
    /**
     * MobileValidator constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value) {
            $isPhone = $this->isPhone($value);
            if (!$isPhone || !preg_match('/^[6-9]\d{8}$/', ltrim($value, '0'))) {
                $message = $this->translator->trans('This value is not valid mobile phone.', [], 'validators');
                $this->context->buildViolation($message)->addViolation();
            }
        }
    }

    /**
     * Check if is phone number.
     */
    private function isPhone(mixed $value): bool
    {
        if (preg_match('/[a-z]/i', $value)) {
            return false;
        }

        foreach (Countries::getNames() as $code => $name) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                if ($phoneUtil->parse($value, strtoupper($code))) {
                    return true;
                }
            } catch (\Exception $exception) {
            }
        }

        return false;
    }
}
