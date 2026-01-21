<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Color;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ColorRepository.
 *
 * @extends ServiceEntityRepository<Color>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ColorRepository extends ServiceEntityRepository
{
    /**
     * ColorRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Color::class);
    }

    /**
     * Get Color[] as array.
     */
    public function findByConfiguration(int $configurationId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.configuration = :configuration')
            ->setParameter('configuration', $configurationId)
            ->getQuery()
            ->getArrayResult();
    }
}
