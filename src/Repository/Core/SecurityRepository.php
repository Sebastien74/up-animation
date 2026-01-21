<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Security;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SecurityRepository.
 *
 * @extends ServiceEntityRepository<Security>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityRepository extends ServiceEntityRepository
{
    /**
     * ConfigurationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Security::class);
    }
}
