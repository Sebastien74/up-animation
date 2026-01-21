<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\ZoneIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ZoneIntlRepository.
 *
 * @extends ServiceEntityRepository<ZoneIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZoneIntlRepository extends ServiceEntityRepository
{
    /**
     * ZoneIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ZoneIntl::class);
    }
}
