<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\InstagramIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * InstagramIntlRepository.
 *
 * @extends ServiceEntityRepository<InstagramIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InstagramIntlRepository extends ServiceEntityRepository
{
    /**
     * InstagramIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, InstagramIntl::class);
    }
}
