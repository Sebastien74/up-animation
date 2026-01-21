<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\Zone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ZoneRepository.
 *
 * @extends ServiceEntityRepository<Zone>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZoneRepository extends ServiceEntityRepository
{
    /**
     * ZoneRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Zone::class);
    }
}
