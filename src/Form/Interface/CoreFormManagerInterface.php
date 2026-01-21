<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Core as CoreManager;

/**
 * CoreFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CoreFormManagerInterface
{
    public function base(): CoreManager\BaseManager;
    public function configuration(): CoreManager\ConfigurationManager;
    public function entityConfiguration(): CoreManager\EntityConfigurationManager;
    public function global(): CoreManager\GlobalManager;
    public function icon(): CoreManager\IconManager;
    public function search(): CoreManager\SearchManager;
    public function session(): CoreManager\SessionManager;
    public function support(): CoreManager\SupportManager;
    public function tree(): CoreManager\TreeManager;
    public function website(): CoreManager\WebsiteManager;
}