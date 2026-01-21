<?php

declare(strict_types=1);

namespace App\Repository\Module\Faq;

use App\Entity\Module\Faq\FaqMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FaqMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<FaqMediaRelation>
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class FaqMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * FaqMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FaqMediaRelation::class);
    }
}
