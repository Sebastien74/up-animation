<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\Google;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GoogleRepository.
 *
 * @extends ServiceEntityRepository<Google>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GoogleRepository extends ServiceEntityRepository
{
    /**
     * GoogleRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Google::class);
    }
}
