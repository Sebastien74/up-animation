<?php

declare(strict_types=1);

namespace App\Repository\Module\Timeline;

use App\Entity\Module\Timeline\Step;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * StepRepository.
 *
 * @extends ServiceEntityRepository<Step>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class StepRepository extends ServiceEntityRepository
{
    /**
     * StepRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Step::class);
    }
}
