<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Module;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * ModuleFormManagerLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ModuleFormManagerLocator::class, 'key' => 'module_form_manager_locator'],
])]
class ModuleFormManagerLocator implements ModuleFormManagerInterface
{
    /**
     * ModuleFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Module\AddLinkManager::class, indexAttribute: 'key')] protected ServiceLocator $linkMenuLocator,
        #[AutowireLocator(Module\CampaignManager::class, indexAttribute: 'key')] protected ServiceLocator $newsletterCampaignLocator,
        #[AutowireLocator(Module\CatalogFeatureValueManager::class, indexAttribute: 'key')] protected ServiceLocator $catalogFeatureValueLocator,
        #[AutowireLocator(Module\CatalogFeatureManager::class, indexAttribute: 'key')] protected ServiceLocator $catalogFeatureLocator,
        #[AutowireLocator(Module\CatalogProductManager::class, indexAttribute: 'key')] protected ServiceLocator $catalogProductLocator,
        #[AutowireLocator(Module\FormCalendarManager::class, indexAttribute: 'key')] protected ServiceLocator $formCalendarLocator,
        #[AutowireLocator(Module\FormManager::class, indexAttribute: 'key')] protected ServiceLocator $formLocator,
        #[AutowireLocator(Module\NewscastDuplicateManager::class, indexAttribute: 'key')] protected ServiceLocator $newscastDuplicateLocator,
        #[AutowireLocator(Module\NewscastManager::class, indexAttribute: 'key')] protected ServiceLocator $newscastLocator,
        #[AutowireLocator(Module\NewscastListingManager::class, indexAttribute: 'key')] protected ServiceLocator $newscastListingLocator,
        #[AutowireLocator(Module\NewscastTeaserManager::class, indexAttribute: 'key')] protected ServiceLocator $newscastTeaserLocator,
        #[AutowireLocator(Module\StepFormManager::class, indexAttribute: 'key')] protected ServiceLocator $stepFormLocator,
        #[AutowireLocator(Module\TableManager::class, indexAttribute: 'key')] protected ServiceLocator $tableLocator,
        #[AutowireLocator(Module\JobManager::class, indexAttribute: 'key')] protected ServiceLocator $jobLocator,
    ) {
    }

    /**
     * To get AddLinkManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function addLinkToMenu(): Module\AddLinkManager
    {
        return $this->linkMenuLocator->get('module_add_link_menu_form_manager');
    }

    /**
     * To get CampaignManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function newsletterCampaign(): Module\CampaignManager
    {
        return $this->newsletterCampaignLocator->get('module_newsletter_campaign_form_manager');
    }

    /**
     * To get CatalogFeatureValueManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function catalogFeatureValue(): Module\CatalogFeatureValueManager
    {
        return $this->catalogFeatureValueLocator->get('module_catalog_feature_value_form_manager');
    }

    /**
     * To get CatalogFeatureManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function catalogFeature(): Module\CatalogFeatureManager
    {
        return $this->catalogFeatureLocator->get('module_catalog_feature_form_manager');
    }

    /**
     * To get CatalogProductManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function catalogProduct(): Module\CatalogProductManager
    {
        return $this->catalogProductLocator->get('module_catalog_product_form_manager');
    }

    /**
     * To get FormCalendarManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function formCalendar(): Module\FormCalendarManager
    {
        return $this->formCalendarLocator->get('module_form_calendar_form_manager');
    }

    /**
     * To get FormManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function form(): Module\FormManager
    {
        return $this->formLocator->get('module_form_form_manager');
    }

    /**
     * To get NewscastDuplicateManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function newscastDuplicate(): Module\NewscastDuplicateManager
    {
        return $this->newscastDuplicateLocator->get('module_newscast_duplicate_form_manager');
    }

    /**
     * To get NewscastManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function newscast(): Module\NewscastManager
    {
        return $this->newscastLocator->get('module_newscast_form_manager');
    }

    /**
     * To get NewscastListingManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function newscastListing(): Module\NewscastListingManager
    {
        return $this->newscastListingLocator->get('module_newscast_listing_form_manager');
    }

    /**
     * To get NewscastTeaserManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function newscastTeaser(): Module\NewscastTeaserManager
    {
        return $this->newscastTeaserLocator->get('module_newscast_teaser_form_manager');
    }

    /**
     * To get StepFormManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function stepForm(): Module\StepFormManager
    {
        return $this->stepFormLocator->get('module_step_form_form_manager');
    }

    /**
     * To get TableManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function table(): Module\TableManager
    {
        return $this->tableLocator->get('module_table_form_manager');
    }

    /**
     * To get JobManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function job(): Module\JobManager
    {
        return $this->jobLocator->get('module_job_form_manager');
    }
}
