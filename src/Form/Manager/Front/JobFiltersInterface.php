<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Module\Recruitment\Listing;

/**
 * JobFiltersInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface JobFiltersInterface
{
    public function getFilters(): array;
    public function getResults(Listing $entity, array $filters = []): array;
}