<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\CssClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CssClassRepository.
 *
 * @extends ServiceEntityRepository<CssClass>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CssClassRepository extends ServiceEntityRepository
{
    /**
     * CssClassRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CssClass::class);
    }
}
