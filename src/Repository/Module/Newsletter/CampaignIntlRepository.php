<?php

declare(strict_types=1);

namespace App\Repository\Module\Newsletter;

use App\Entity\Module\Newsletter\CampaignIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CampaignIntlRepository.
 *
 * @extends ServiceEntityRepository<CampaignIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CampaignIntlRepository extends ServiceEntityRepository
{
    /**
     * CampaignIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CampaignIntl::class);
    }
}
