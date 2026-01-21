<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\SocialNetwork;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SocialNetworkRepository.
 *
 * @extends ServiceEntityRepository<SocialNetwork>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SocialNetworkRepository extends ServiceEntityRepository
{
    /**
     * SocialNetworkRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SocialNetwork::class);
    }
}
