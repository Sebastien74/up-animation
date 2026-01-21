<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;

/**
 * EntityImportV6Interface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface EntityImportV6Interface
{
    public function entities(Website $website): array;

    public function execute(Website $website, int $importId): mixed;
}
