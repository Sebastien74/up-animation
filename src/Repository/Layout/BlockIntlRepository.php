<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\BlockIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlockIntlRepository.
 *
 * @extends ServiceEntityRepository<BlockIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockIntlRepository extends ServiceEntityRepository
{
    /**
     * BlockIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, BlockIntl::class);
    }
}
