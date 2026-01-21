<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Layout as LayoutManager;

/**
 * LayoutFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface LayoutFormManagerInterface
{
    public function action(): LayoutManager\ActionManager;
    public function blockDuplicate(): LayoutManager\BlockDuplicateManager;
    public function block(): LayoutManager\BlockManager;
    public function colDuplicate(): LayoutManager\ColDuplicateManager;
    public function fieldConfiguration(): LayoutManager\FieldConfigurationManager;
    public function layoutConfiguration(): LayoutManager\LayoutConfigurationManager;
    public function layoutDuplicate(): LayoutManager\LayoutDuplicateManager;
    public function layout(): LayoutManager\LayoutManager;
    public function pageDuplicate(): LayoutManager\PageDuplicateManager;
    public function page(): LayoutManager\PageManager;
    public function zoneConfiguration(): LayoutManager\ZoneConfigurationManager;
    public function colConfiguration(): LayoutManager\ColConfigurationManager;
    public function blockConfiguration(): LayoutManager\BlockConfigurationManager;
    public function zoneDuplicate(): LayoutManager\ZoneDuplicateManager;
    public function zone(): LayoutManager\ZoneManager;
}