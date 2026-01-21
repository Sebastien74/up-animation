<?php

declare(strict_types=1);

namespace App\Repository\Module\Slider;

use App\Entity\Module\Slider\Slider;
use App\Entity\Module\Slider\SliderMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SliderRepository.
 *
 * @extends ServiceEntityRepository<Slider>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SliderRepository extends ServiceEntityRepository
{
    /**
     * SliderRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Slider::class);
    }

    /**
     * Find one by with relations.
     */
    public function findOneByWithRelations(string $column, mixed $value): ?Slider
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.mediaRelations', 'mr')
            ->leftJoin('mr.intl', 'mri')
            ->leftJoin('mr.media', 'm')
            ->addSelect('mr', 'mri', 'm')
            ->where('s.'.$column.' = :value')
            ->setParameter('value', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
