<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Logo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LogoRepository.
 *
 * @extends ServiceEntityRepository<Logo>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LogoRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Logo::class);
    }
}
