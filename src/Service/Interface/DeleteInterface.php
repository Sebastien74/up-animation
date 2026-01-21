<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\Admin\DeleteService;
use App\Service\Delete\ContactDeleteService;

/**
 * DeleteInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface DeleteInterface
{
    public function coreService(): DeleteService;
    public function contactsService(): ContactDeleteService;
}