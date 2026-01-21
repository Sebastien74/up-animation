<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\CompanyAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CompanyAddressRepository.
 *
 * @extends ServiceEntityRepository<CompanyAddress>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CompanyAddressRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CompanyAddress::class);
    }
}
