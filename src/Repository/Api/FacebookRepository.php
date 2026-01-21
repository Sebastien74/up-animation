<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\Facebook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FacebookRepository.
 *
 * @extends ServiceEntityRepository<Facebook>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FacebookRepository extends ServiceEntityRepository
{
    /**
     * FacebookRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Facebook::class);
    }
}
