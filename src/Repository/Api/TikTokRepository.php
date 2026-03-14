<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\TikTok;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TikTokRepository.
 *
 * @extends ServiceEntityRepository<TikTok>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TikTokRepository extends ServiceEntityRepository
{
    /**
     * TikTokRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, TikTok::class);
    }
}
