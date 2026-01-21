<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Newsletter\Campaign;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * CampaignManager.
 *
 * Manage admin Newsletter form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CampaignManager::class, 'key' => 'module_newsletter_campaign_form_manager'],
])]
class CampaignManager
{
    /**
     * CampaignManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     *
     * @throws \Exception
     */
    public function prePersist(Campaign $campaign, Website $website): void
    {
        $campaign->setSecurityKey($this->coreLocator->alphanumericKey());

        $this->coreLocator->em()->persist($campaign);
    }
}
