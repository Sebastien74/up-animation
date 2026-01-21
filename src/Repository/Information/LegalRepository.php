<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\Legal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LegalRepository.
 *
 * @extends ServiceEntityRepository<Legal>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LegalRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Legal::class);
    }
}
