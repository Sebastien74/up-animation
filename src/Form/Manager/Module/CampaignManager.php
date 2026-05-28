<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Newsletter\Campaign;
use App\Entity\Module\Newsletter\Email;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
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
readonly class CampaignManager
{
    /**
     * CampaignManager constructor.
     */
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     *
     * @throws Exception
     */
    public function prePersist(Campaign $campaign, Website $website): void
    {
        $campaign->setSecurityKey($this->coreLocator->alphanumericKey());

        $this->coreLocator->em()->persist($campaign);
    }

    /**
     * To remove Email[] with expired token (uncompleted registration).
     */
    public function removeExpiredToken(): void
    {
        $expiredEmails = $this->coreLocator->em()->getRepository(Email::class)->findEmailsWithExpiredToken();
        if ($expiredEmails) {
            foreach ($expiredEmails as $email) {
                $this->coreLocator->em()->remove($email);
            }
            $this->coreLocator->em()->flush();
        }
    }
}
