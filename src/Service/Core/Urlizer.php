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
     * To slugify a string.
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

    /**
     * Slugifie un chemin segment par segment, en préservant la structure des "/".
     *
     * Chaque segment est urlisé (folding casse/accents) puis les segments sont joints
     * par "_". Contrairement à urlize() qui écrase "/" en "-" et provoque des collisions
     * ("a/b" ≡ "a-b"), les frontières de segments sont conservées : "x/annecy" ≠ "x-annecy".
     * Le "_" est distinct du "-" intra-segment et n'est pas un caractère réservé des clés
     * de cache PSR-6 (contrairement au "/") — adapté à la construction de clés de redirection.
     */
    public static function urlizePath(?string $path = null, ?string $separator = '-'): ?string
    {
        if (!is_string($path)) {
            return $path;
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ('' !== $segment) {
                $segments[] = self::urlize($segment, $separator);
            }
        }

        return implode('_', $segments);
    }
}