<?php

declare(strict_types=1);

namespace App\Repository\Module\Faq;

use App\Entity\Module\Faq\FaqIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaqIntl>
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class FaqIntlRepository extends ServiceEntityRepository
{
    /**
     * FaqIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FaqIntl::class);
    }
}
