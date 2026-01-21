<?php

declare(strict_types=1);

namespace App\Repository\Module\Faq;

use App\Entity\Module\Faq\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * QuestionRepository.
 *
 * @extends ServiceEntityRepository<Question>
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class QuestionRepository extends ServiceEntityRepository
{
    /**
     * QuestionRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Question::class);
    }
}
