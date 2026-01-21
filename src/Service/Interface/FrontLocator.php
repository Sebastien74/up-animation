<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Content\CatalogSearchServiceInterface;
use App\Service\Content\MenuServiceInterface;

/**
 * FrontFormManagerLocator.
 *
 * To load base Services
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class FrontLocator implements FrontLocatorInterface
{
    /**
     * FrontFormManagerLocator constructor.
     */
    public function __construct(
        private MenuServiceInterface $menuService,
        private CatalogSearchServiceInterface $catalogSearchService,
    ) {
    }

    /**
     * To get MenuServiceInterface.
     */
    public function menuService(): MenuServiceInterface
    {
        return $this->menuService;
    }

    /**
     * To get CatalogSearchServiceInterface.
     */
    public function catalogSearch(): CatalogSearchServiceInterface
    {
        return $this->catalogSearchService;
    }
}
