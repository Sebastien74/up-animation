<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Layout as LayoutManager;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * LayoutFormFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => LayoutFormFormManagerLocator::class, 'key' => 'layout_form_manager'],
])]
class LayoutFormFormManagerLocator implements LayoutFormManagerInterface
{
    /**
     * LayoutFormFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(LayoutManager\ActionManager::class, indexAttribute: 'key')] protected ServiceLocator $actionLocator,
        #[AutowireLocator(LayoutManager\BlockDuplicateManager::class, indexAttribute: 'key')] protected ServiceLocator $blockDuplicateLocator,
        #[AutowireLocator(LayoutManager\BlockManager::class, indexAttribute: 'key')] protected ServiceLocator $blockLocator,
        #[AutowireLocator(LayoutManager\ColDuplicateManager::class, indexAttribute: 'key')] protected ServiceLocator $colDuplicateLocator,
        #[AutowireLocator(LayoutManager\FieldConfigurationManager::class, indexAttribute: 'key')] protected ServiceLocator $fieldConfigurationLocator,
        #[AutowireLocator(LayoutManager\LayoutConfigurationManager::class, indexAttribute: 'key')] protected ServiceLocator $layoutConfigurationLocator,
        #[AutowireLocator(LayoutManager\LayoutDuplicateManager::class, indexAttribute: 'key')] protected ServiceLocator $layoutDuplicateLocator,
        #[AutowireLocator(LayoutManager\LayoutManager::class, indexAttribute: 'key')] protected ServiceLocator $layoutLocator,
        #[AutowireLocator(LayoutManager\PageDuplicateManager::class, indexAttribute: 'key')] protected ServiceLocator $pageDuplicateLocator,
        #[AutowireLocator(LayoutManager\PageManager::class, indexAttribute: 'key')] protected ServiceLocator $pageLocator,
        #[AutowireLocator(LayoutManager\ZoneConfigurationManager::class, indexAttribute: 'key')] protected ServiceLocator $zoneConfigurationLocator,
        #[AutowireLocator(LayoutManager\ColConfigurationManager::class, indexAttribute: 'key')] protected ServiceLocator $colConfigurationLocator,
        #[AutowireLocator(LayoutManager\BlockConfigurationManager::class, indexAttribute: 'key')] protected ServiceLocator $blockConfigurationLocator,
        #[AutowireLocator(LayoutManager\ZoneDuplicateManager::class, indexAttribute: 'key')] protected ServiceLocator $zoneDuplicateLocator,
        #[AutowireLocator(LayoutManager\ZoneManager::class, indexAttribute: 'key')] protected ServiceLocator $zoneLocator,
    ) {
    }

    /**
     * To get ActionManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function action(): LayoutManager\ActionManager
    {
        return $this->actionLocator->get('layout_action_form_manager');
    }

    /**
     * To get BlockDuplicateManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function blockDuplicate(): LayoutManager\BlockDuplicateManager
    {
        return $this->blockDuplicateLocator->get('layout_block_duplicate_form_manager');
    }

    /**
     * To get BlockManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function block(): LayoutManager\BlockManager
    {
        return $this->blockLocator->get('layout_block_form_manager');
    }

    /**
     * To get ColDuplicateManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function colDuplicate(): LayoutManager\ColDuplicateManager
    {
        return $this->colDuplicateLocator->get('layout_col_duplicate_form_manager');
    }

    /**
     * To get FieldConfigurationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function fieldConfiguration(): LayoutManager\FieldConfigurationManager
    {
        return $this->fieldConfigurationLocator->get('layout_field_configuration_form_manager');
    }

    /**
     * To get LayoutConfigurationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function layoutConfiguration(): LayoutManager\LayoutConfigurationManager
    {
        return $this->layoutConfigurationLocator->get('layout_configuration_form_manager');
    }

    /**
     * To get LayoutDuplicateManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function layoutDuplicate(): LayoutManager\LayoutDuplicateManager
    {
        return $this->layoutDuplicateLocator->get('layout_duplicate_form_manager');
    }

    /**
     * To get LayoutManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function layout(): LayoutManager\LayoutManager
    {
        return $this->layoutLocator->get('layout_form_manager');
    }

    /**
     * To get PageDuplicateManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function pageDuplicate(): LayoutManager\PageDuplicateManager
    {
        return $this->pageDuplicateLocator->get('layout_page_duplicate_form_manager');
    }

    /**
     * To get PageManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function page(): LayoutManager\PageManager
    {
        return $this->pageLocator->get('layout_page_form_manager');
    }

    /**
     * To get ZoneConfigurationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function zoneConfiguration(): LayoutManager\ZoneConfigurationManager
    {
        return $this->zoneConfigurationLocator->get('layout_zone_configuration_form_manager');
    }

    /**
     * To get ColConfigurationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function colConfiguration(): LayoutManager\ColConfigurationManager
    {
        return $this->colConfigurationLocator->get('layout_col_configuration_form_manager');
    }

    /**
     * To get BlockConfigurationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function blockConfiguration(): LayoutManager\BlockConfigurationManager
    {
        return $this->blockConfigurationLocator->get('layout_block_configuration_form_manager');
    }

    /**
     * To get ZoneDuplicateManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function zoneDuplicate(): LayoutManager\ZoneDuplicateManager
    {
        return $this->zoneDuplicateLocator->get('layout_zone_duplicate_form_manager');
    }

    /**
     * To get ZoneManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function zone(): LayoutManager\ZoneManager
    {
        return $this->zoneLocator->get('layout_zone_form_manager');
    }
}
