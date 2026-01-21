<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\Custom;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CustomRepository.
 *
 * @extends ServiceEntityRepository<Custom>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CustomRepository extends ServiceEntityRepository
{
    /**
     * CustomRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Custom::class);
    }
}
