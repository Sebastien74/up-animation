<?php

declare(strict_types=1);

namespace App\Entity\Module\Newsletter;

use App\Entity\BaseIntl;
use App\Repository\Module\Newsletter\CampaignIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CampaignIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_newsletter_campaign_intls')]
#[ORM\Entity(repositoryClass: CampaignIntlRepository::class)]
class CampaignIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Campaign::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Campaign $campaign = null;

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }
}
