<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * RoleRepository.
 *
 * @extends ServiceEntityRepository<Role>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RoleRepository extends ServiceEntityRepository
{
    /**
     * RoleRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Role::class);
    }
}
