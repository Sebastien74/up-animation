<?php

declare(strict_types=1);

namespace App\Repository\Module\Recruitment;

use App\Entity\Module\Recruitment\JobIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * JobIntlRepository.
 *
 * @extends ServiceEntityRepository<JobIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class JobIntlRepository extends ServiceEntityRepository
{
    /**
     * JobIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, JobIntl::class);
    }
}
