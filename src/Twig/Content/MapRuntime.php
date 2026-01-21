<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Module\Map\Map;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * MapRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MapRuntime implements RuntimeExtensionInterface
{
    /**
     * To get points group by categories.
     */
    public function mapPointsGroupByCategories(Map $map): array
    {
        $groups = [];
        foreach ($map->getPoints() as $point) {
            foreach ($point->getCategories() as $category) {
                $groups[$category->getId()][] = $point;
            }
            if ($point->getCategories()->isEmpty()) {
                $groups[] = $point;
            }
        }

        return $groups;
    }
}
