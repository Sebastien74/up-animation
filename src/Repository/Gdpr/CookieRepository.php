<?php

declare(strict_types=1);

namespace App\Repository\Gdpr;

use App\Entity\Gdpr\Cookie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CookieRepository.
 *
 * @extends ServiceEntityRepository<Cookie>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CookieRepository extends ServiceEntityRepository
{
    /**
     * CookieRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Cookie::class);
    }
}
