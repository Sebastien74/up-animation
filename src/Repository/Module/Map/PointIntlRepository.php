<?php

declare(strict_types=1);

namespace App\Repository\Module\Map;

use App\Entity\Module\Map\PointIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PointIntlRepository.
 *
 * @extends ServiceEntityRepository<PointIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PointIntlRepository extends ServiceEntityRepository
{
    /**
     * PointIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, PointIntl::class);
    }
}
