<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Gdpr\CookieManager;

/**
 * GdprFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface GdprFormManagerInterface
{
    public function cookie(): CookieManager;
}