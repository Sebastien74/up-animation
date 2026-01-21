<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\Action;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ActionRepository.
 *
 * @extends ServiceEntityRepository<Action>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionRepository extends ServiceEntityRepository
{
    /**
     * ActionRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Action::class);
    }
}
