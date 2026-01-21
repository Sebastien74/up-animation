<?php

declare(strict_types=1);

namespace App\Repository\Module\Faq;

use App\Entity\Module\Faq\QuestionIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * QuestionIntlRepository.
 *
 * @extends ServiceEntityRepository<QuestionIntl>
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class QuestionIntlRepository extends ServiceEntityRepository
{
    /**
     * FaqRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, QuestionIntl::class);
    }
}
