<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Link;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LinkRepository.
 *
 * @extends ServiceEntityRepository<Link>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LinkRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Link::class);
    }
}
