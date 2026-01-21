<?php

declare(strict_types=1);

namespace App\Repository\Module\Portfolio;

use App\Entity\Module\Portfolio\CardIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CardIntlRepository.
 *
 * @extends ServiceEntityRepository<CardIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CardIntlRepository extends ServiceEntityRepository
{
    /**
     * CardIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CardIntl::class);
    }
}
