<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\FacebookIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FacebookIntlRepository.
 *
 * @extends ServiceEntityRepository<FacebookIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FacebookIntlRepository extends ServiceEntityRepository
{
    /**
     * FacebookIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FacebookIntl::class);
    }
}
