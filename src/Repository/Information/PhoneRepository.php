<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\Phone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PhoneRepository.
 *
 * @extends ServiceEntityRepository<Phone>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PhoneRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Phone::class);
    }
}
