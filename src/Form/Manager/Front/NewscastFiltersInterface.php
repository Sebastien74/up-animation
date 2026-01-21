<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Module\Newscast\Listing;
use App\Entity\Module\Newscast\Teaser;

/**
 * NewscastFiltersInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface NewscastFiltersInterface
{
    public function getFilters(): array;
    public function getResults(Listing|Teaser $entity, array $filters = []): array;
}