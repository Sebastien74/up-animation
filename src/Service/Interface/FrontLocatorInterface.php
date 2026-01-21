<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Content\CatalogSearchServiceInterface;
use App\Service\Content\MenuServiceInterface;

/**
 * FrontLocatorInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface FrontLocatorInterface
{
    public function menuService(): MenuServiceInterface;
    public function catalogSearch(): CatalogSearchServiceInterface;
}
