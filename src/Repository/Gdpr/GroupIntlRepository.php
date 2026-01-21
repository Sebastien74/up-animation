<?php

declare(strict_types=1);

namespace App\Repository\Gdpr;

use App\Entity\Gdpr\GroupIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GroupIntlRepository.
 *
 * @extends ServiceEntityRepository<GroupIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupIntlRepository extends ServiceEntityRepository
{
    /**
     * GroupIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, GroupIntl::class);
    }
}
