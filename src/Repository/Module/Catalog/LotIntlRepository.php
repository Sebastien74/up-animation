<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\LotIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LotIntlRepository.
 *
 * @extends ServiceEntityRepository<LotIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LotIntlRepository extends ServiceEntityRepository
{
    /**
     * LotIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, LotIntl::class);
    }
}
