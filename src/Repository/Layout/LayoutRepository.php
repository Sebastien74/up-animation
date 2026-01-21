<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\Layout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LayoutRepository.
 *
 * @extends ServiceEntityRepository<Layout>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutRepository extends ServiceEntityRepository
{
    /**
     * LayoutRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Layout::class);
    }
}
