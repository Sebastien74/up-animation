<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\BlockTypeIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlockTypeIntlRepository.
 *
 * @extends ServiceEntityRepository<BlockTypeIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockTypeIntlRepository extends ServiceEntityRepository
{
    /**
     * BlockTypeIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, BlockTypeIntl::class);
    }
}
