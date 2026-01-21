<?php

declare(strict_types=1);

namespace App\Service\Development;

/**
 * CopyBundleInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CopyBundleInterface
{
    public function execute(): void;
}