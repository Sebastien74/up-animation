<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\FieldConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FieldConfiguration.
 *
 * @extends ServiceEntityRepository<FieldConfiguration>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldConfigurationRepository extends ServiceEntityRepository
{
    /**
     * FieldConfigurationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FieldConfiguration::class);
    }
}
