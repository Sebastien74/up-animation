<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\Instagram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * InstagramRepository.
 *
 * @extends ServiceEntityRepository<Instagram>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InstagramRepository extends ServiceEntityRepository
{
    /**
     * InstagramRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Instagram::class);
    }
}
