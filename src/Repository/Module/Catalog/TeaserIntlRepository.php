<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\TeaserIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TeaserIntlRepository.
 *
 * @extends ServiceEntityRepository<TeaserIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TeaserIntlRepository extends ServiceEntityRepository
{
    /**
     * TeaserIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, TeaserIntl::class);
    }
}
