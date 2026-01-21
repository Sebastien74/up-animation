<?php

declare(strict_types=1);

namespace App\Repository\Information;

use App\Entity\Information\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * EmailRepository.
 *
 * @extends ServiceEntityRepository<Email>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EmailRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Email::class);
    }
}
