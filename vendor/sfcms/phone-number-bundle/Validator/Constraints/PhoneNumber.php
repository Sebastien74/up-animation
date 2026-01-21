<?php

/*
 * This file is part of the Symfony2 PhoneNumberBundle.
 *
 * (c) University of Cambridge
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Misd\PhoneNumberBundle\Validator\Constraints;

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;

/**
 * Phone number constraint.
 *
 * @Annotation
 */
class PhoneNumber extends Constraint
{
    const ANY = 'any';
    const FIXED_LINE = 'fixed_line';
    const MOBILE = 'mobile';
    const PAGER = 'pager';
    const PERSONAL_NUMBER = 'personal_number';
    const PREMIUM_RATE = 'premium_rate';
    const SHARED_COST = 'shared_cost';
    const TOLL_FREE = 'toll_free';
    const UAN = 'uan';
    const VOIP = 'voip';
    const VOICEMAIL = 'voicemail';

    const INVALID_PHONE_NUMBER_ERROR = 'ca23f4ca-38f4-4325-9bcc-eb570a4abe7f';

    protected static array $errorNames = array(
        self::INVALID_PHONE_NUMBER_ERROR => 'INVALID_PHONE_NUMBER_ERROR',
    );

    public mixed $message = null;
    public string $type = self::ANY;
    public $defaultRegion = PhoneNumberUtil::UNKNOWN_REGION;

    public function getType(): string
    {
        return match ($this->type) {
            self::FIXED_LINE, self::MOBILE, self::PAGER, self::PERSONAL_NUMBER, self::PREMIUM_RATE, self::SHARED_COST, self::TOLL_FREE, self::UAN, self::VOIP, self::VOICEMAIL => $this->type,
            default => self::ANY,
        };

    }

    public function getMessage()
    {
        if (null !== $this->message) {
            return $this->message;
        }

        return match ($this->type) {
            self::FIXED_LINE => 'This value is not a valid fixed-line number.',
            self::MOBILE => 'This value is not a valid mobile number.',
            self::PAGER => 'This value is not a valid pager number.',
            self::PERSONAL_NUMBER => 'This value is not a valid personal number.',
            self::PREMIUM_RATE => 'This value is not a valid premium-rate number.',
            self::SHARED_COST => 'This value is not a valid shared-cost number.',
            self::TOLL_FREE => 'This value is not a valid toll-free number.',
            self::UAN => 'This value is not a valid UAN.',
            self::VOIP => 'This value is not a valid VoIP number.',
            self::VOICEMAIL => 'This value is not a valid voicemail access number.',
            default => 'This value is not a valid phone number.',
        };

    }
}
