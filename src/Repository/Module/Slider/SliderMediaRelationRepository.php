<?php

declare(strict_types=1);

namespace App\Repository\Module\Slider;

use App\Entity\Module\Slider\SliderMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SliderMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<SliderMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SliderMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * BlockMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SliderMediaRelation::class);
    }
}
