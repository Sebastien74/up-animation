<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Media\Thumb;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ThumbRepository.
 *
 * @extends ServiceEntityRepository<Thumb>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ThumbRepository extends ServiceEntityRepository
{
    /**
     * ThumbRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Thumb::class);
    }
}
