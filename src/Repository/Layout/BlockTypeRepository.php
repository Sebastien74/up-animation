<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\BlockType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlockTypeRepository.
 *
 * @extends ServiceEntityRepository<BlockType>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockTypeRepository extends ServiceEntityRepository
{
    /**
     * BlockTypeRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, BlockType::class);
    }

    /**
     * Find intls[].
     */
    public function findForIntls(): array
    {
        return $this->createQueryBuilder('b')
            ->select('b')
            ->leftJoin('b.intls', 'i')
            ->addSelect('i')
            ->getQuery()
            ->getResult();
    }
}
