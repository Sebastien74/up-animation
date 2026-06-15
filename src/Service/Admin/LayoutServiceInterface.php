<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Layout;
use App\Entity\Layout\Zone;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * LayoutServiceInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface LayoutServiceInterface
{
    public function resetMargins(Zone $zone): JsonResponse;

    public function standardizeMarginsEL(mixed $entity): JsonResponse;

    public function restoreMarginsEL(mixed $entity): JsonResponse;

    public function standardizeLayoutMargins(Layout $layout): JsonResponse;

    public function restoreLayoutMargins(Layout $layout): JsonResponse;
}