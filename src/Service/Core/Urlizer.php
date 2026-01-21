<?php

declare(strict_types=1);

namespace App\Service\Core;

use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Urlizer.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class Urlizer
{
    /**
     * To slugify a string
     */
    public static function urlize(?string $string = null, ?string $separator = '-'): ?string
    {
        if (!is_string($string)) {
            return $string;
        }

        return (new AsciiSlugger())
            ->slug($string, $separator)
            ->lower()
            ->toString();
    }
}