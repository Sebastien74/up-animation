<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Api as ApiManager;

/**
 * ApiFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface ApiFormManagerInterface
{
    public function custom(): ApiManager\CustomManager;
    public function facebook(): ApiManager\FacebookManager;
    public function google(): ApiManager\GoogleManager;
    public function instagram(): ApiManager\InstagramManager;
}