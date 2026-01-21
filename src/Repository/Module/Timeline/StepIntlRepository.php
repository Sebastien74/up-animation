<?php

declare(strict_types=1);

namespace App\Repository\Module\Timeline;

use App\Entity\Module\Timeline\StepIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * StepIntlRepository.
 *
 * @extends ServiceEntityRepository<StepIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StepIntlRepository extends ServiceEntityRepository
{
    /**
     * StepIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, StepIntl::class);
    }
}
