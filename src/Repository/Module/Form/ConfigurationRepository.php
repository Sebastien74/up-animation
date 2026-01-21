<?php

declare(strict_types=1);

namespace App\Repository\Module\Form;

use App\Entity\Module\Form\Configuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ConfigurationRepository.
 *
 * @extends ServiceEntityRepository<Configuration>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    /**
     * ConfigurationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Configuration::class);
    }
}
