<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Module;

/**
 * ModuleFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface ModuleFormManagerInterface
{
    public function addLinkToMenu(): Module\AddLinkManager;
    public function newsletterCampaign(): Module\CampaignManager;
    public function catalogFeatureValue(): Module\CatalogFeatureValueManager;
    public function catalogFeature(): Module\CatalogFeatureManager;
    public function catalogProduct(): Module\CatalogProductManager;
    public function formCalendar(): Module\FormCalendarManager;
    public function form(): Module\FormManager;
    public function newscastDuplicate(): Module\NewscastDuplicateManager;
    public function newscast(): Module\NewscastManager;
    public function newscastListing(): Module\NewscastListingManager;
    public function newscastTeaser(): Module\NewscastTeaserManager;
    public function stepForm(): Module\StepFormManager;
    public function table(): Module\TableManager;
    public function job(): Module\JobManager;
}