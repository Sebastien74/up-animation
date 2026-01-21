<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CompanyRepository.
 *
 * @extends ServiceEntityRepository<Company>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Company::class);
    }
}
