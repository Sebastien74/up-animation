<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Information;

/**
 * InformationFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface InformationFormManagerInterface
{
    public function information(): Information\InformationManager;

    public function networks(): Information\SocialNetworkManager;
}