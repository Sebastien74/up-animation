<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\GoogleIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GoogleIntlRepository.
 *
 * @extends ServiceEntityRepository<GoogleIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GoogleIntlRepository extends ServiceEntityRepository
{
    /**
     * GoogleIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, GoogleIntl::class);
    }
}
