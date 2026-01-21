<?php

declare(strict_types=1);

namespace App\Service\Development\Cms;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;

/**
 * PageImportV4Interface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface PageImportV4Interface
{
    public function entities(Website $website): array;
    public function execute(Website $website, int $importId): ?Page;
}