<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Transition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TransitionRepository.
 *
 * @extends ServiceEntityRepository<Transition>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TransitionRepository extends ServiceEntityRepository
{
    /**
     * TransitionRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Transition::class);
    }
}
