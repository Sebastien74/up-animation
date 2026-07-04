<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * EmailDomainValidator.
 *
 * Check if e-mail domain accepts mails (MX record, fallback A/AAAA)
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailDomainValidator extends ConstraintValidator
{
    /** DNS lookups already resolved during this request (one lookup per domain). */
    private static array $resolved = [];

    /**
     * EmailDomainValidator constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* Empty values and syntax errors are handled by NotBlank/Email constraints */
        if (!$value || !is_string($value) || !function_exists('checkdnsrr')) {
            return;
        }

        $at = strrpos($value, '@');
        if (false === $at) {
            return;
        }

        $domain = substr($value, $at + 1);
        if ('' === $domain) {
            return;
        }

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
            if ($ascii) {
                $domain = $ascii;
            }
        }
        $domain = rtrim(strtolower($domain), '.');

        if (!$this->hasMailServer($domain)) {
            $this->context->buildViolation($this->translator->trans("Le domaine de cette adresse e-mail ne peut pas recevoir d'e-mails. Vérifiez votre saisie.", [], 'validators'))->addViolation();
        }
    }

    /**
     * Check DNS records: MX first, fallback to A/AAAA (RFC 5321).
     */
    private function hasMailServer(string $domain): bool
    {
        if (isset(self::$resolved[$domain])) {
            return self::$resolved[$domain];
        }

        $hostname = $domain.'.'; // trailing dot: prevent local search-domain resolution
        $valid = checkdnsrr($hostname, 'MX') || checkdnsrr($hostname, 'A') || checkdnsrr($hostname, 'AAAA');

        return self::$resolved[$domain] = $valid;
    }
}
