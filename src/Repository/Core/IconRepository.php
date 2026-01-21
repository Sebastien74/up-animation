<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Icon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * IconRepository.
 *
 * @extends ServiceEntityRepository<Icon>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IconRepository extends ServiceEntityRepository
{
    /**
     * IconRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Icon::class);
    }
}
