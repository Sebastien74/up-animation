<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Translation;

/**
 * IntlFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface IntlFormManagerInterface
{
    public function front(): Translation\FrontManager;
    public function intl(): Translation\IntlManager;
    public function unit(): Translation\UnitManager;
}