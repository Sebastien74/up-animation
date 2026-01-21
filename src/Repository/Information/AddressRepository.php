<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AddressRepository.
 *
 * @extends ServiceEntityRepository<Address>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AddressRepository extends ServiceEntityRepository
{
    /**
     * AddressRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Address::class);
    }
}
