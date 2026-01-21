<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Module\Catalog\Listing;

/**
 * CatalogSearchServiceInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CatalogSearchServiceInterface
{
    public function execute(Listing $listing, array $data = [], ?string $locale = null): iterable;
}