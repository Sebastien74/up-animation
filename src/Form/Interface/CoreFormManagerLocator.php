<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Core as CoreManager;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * CoreFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CoreFormManagerLocator::class, 'key' => 'core_form_manager_locator'],
])]
class CoreFormManagerLocator implements CoreFormManagerInterface
{
    /**
     * CoreFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(CoreManager\BaseManager::class, indexAttribute: 'key')] protected ServiceLocator $baseLocator,
        #[AutowireLocator(CoreManager\ConfigurationManager::class, indexAttribute: 'key')] protected ServiceLocator $configLocator,
        #[AutowireLocator(CoreManager\EntityConfigurationManager::class, indexAttribute: 'key')] protected ServiceLocator $entityConfigLocator,
        #[AutowireLocator(CoreManager\GlobalManager::class, indexAttribute: 'key')] protected ServiceLocator $globalLocator,
        #[AutowireLocator(CoreManager\IconManager::class, indexAttribute: 'key')] protected ServiceLocator $iconLocator,
        #[AutowireLocator(CoreManager\SearchManager::class, indexAttribute: 'key')] protected ServiceLocator $searchLocator,
        #[AutowireLocator(CoreManager\SessionManager::class, indexAttribute: 'key')] protected ServiceLocator $sessionLocator,
        #[AutowireLocator(CoreManager\SupportManager::class, indexAttribute: 'key')] protected ServiceLocator $supportLocator,
        #[AutowireLocator(CoreManager\TreeManager::class, indexAttribute: 'key')] protected ServiceLocator $treeLocator,
        #[AutowireLocator(CoreManager\WebsiteManager::class, indexAttribute: 'key')] protected ServiceLocator $websiteLocator,
    ) {
    }

    /**
     * To get BaseManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function base(): CoreManager\BaseManager
    {
        return $this->baseLocator->get('core_base_form_manager');
    }

    /**
     * To get ConfigurationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function configuration(): CoreManager\ConfigurationManager
    {
        return $this->configLocator->get('core_configuration_form_manager');
    }

    /**
     * To get EntityConfigurationManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function entityConfiguration(): CoreManager\EntityConfigurationManager
    {
        return $this->entityConfigLocator->get('core_entity_configuration_form_manager');
    }

    /**
     * To get GlobalManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function global(): CoreManager\GlobalManager
    {
        return $this->globalLocator->get('core_global_form_manager');
    }

    /**
     * To get IconManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function icon(): CoreManager\IconManager
    {
        return $this->iconLocator->get('core_icon_form_manager');
    }

    /**
     * To get SearchManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function search(): CoreManager\SearchManager
    {
        return $this->searchLocator->get('core_search_form_manager');
    }

    /**
     * To get SessionManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function session(): CoreManager\SessionManager
    {
        return $this->sessionLocator->get('core_session_form_manager');
    }

    /**
     * To get SupportManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function support(): CoreManager\SupportManager
    {
        return $this->supportLocator->get('core_support_form_manager');
    }

    /**
     * To get TreeManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function tree(): CoreManager\TreeManager
    {
        return $this->treeLocator->get('core_tree_form_manager');
    }

    /**
     * To get WebsiteManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function website(): CoreManager\WebsiteManager
    {
        return $this->websiteLocator->get('core_website_form_manager');
    }
}
