<?php

declare(strict_types=1);

namespace App\Model\Admin;

/**
 * ModulePageUsage.
 *
 * Lightweight projection of a page where a module is used (admin index column).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class ModulePageUsage
{
    public function __construct(
        public string $name,
        public ?string $href,
        public bool $online,
    ) {
    }
}
