<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\LayoutConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LayoutConfigurationRepository.
 *
 * @extends ServiceEntityRepository<LayoutConfiguration>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutConfigurationRepository extends ServiceEntityRepository
{
    /**
     * LayoutConfigurationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, LayoutConfiguration::class);
    }
}
