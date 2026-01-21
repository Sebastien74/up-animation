<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\ActionIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ActionIntlRepository.
 *
 * @extends ServiceEntityRepository<ActionIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionIntlRepository extends ServiceEntityRepository
{
    /**
     * ActionIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ActionIntl::class);
    }
}
