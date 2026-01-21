<?php

declare(strict_types=1);

namespace App\Model;

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Countries;

/**
 * FunctionsModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FunctionModel
{
    /**
     * Check if is phone.
     */
    protected static function isPhone(mixed $var): bool
    {
        if (!is_string($var) || strlen($var) < 7) {
            return false;
        }

        static $phoneUtil = null;
        if (null === $phoneUtil) {
            $phoneUtil = PhoneNumberUtil::getInstance();
        }

        static $countries = null;
        if (null === $countries) {
            $countries = array_keys(Countries::getNames());
        }

        foreach ($countries as $code) {
            try {
                if ($phoneUtil->parse($var, strtoupper($code))) {
                    return true;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }

        return false;
    }
}
