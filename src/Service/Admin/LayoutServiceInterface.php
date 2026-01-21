<?php

declare(strict_types=1);

namespace App\Service\Admin;

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
}