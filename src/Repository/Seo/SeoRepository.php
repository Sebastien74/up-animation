<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Seo\Seo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SeoRepository.
 *
 * @extends ServiceEntityRepository<Seo>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeoRepository extends ServiceEntityRepository
{
    /**
     * SeoRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Seo::class);
    }
}
