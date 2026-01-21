<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Import;

/**
 * ImportInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface ImportInterface
{
    public function productsService(): Import\ImportProductsService;
}
