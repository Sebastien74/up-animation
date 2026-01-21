<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;

/**
 * NewscastsImportV4Interface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface NewscastsImportV4Interface
{
    public function entities(Website $website): array;
    public function execute(Website $website, int $importId): void;
}