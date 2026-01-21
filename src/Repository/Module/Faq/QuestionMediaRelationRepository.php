<?php

declare(strict_types=1);

namespace App\Repository\Module\Faq;

use App\Entity\Module\Faq\QuestionMediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * QuestionMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<QuestionMediaRelation>
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class QuestionMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * QuestionMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, QuestionMediaRelation::class);
    }
}
