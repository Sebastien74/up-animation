<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\ListingIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ListingIntlRepository.
 *
 * @extends ServiceEntityRepository<ListingIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ListingIntlRepository extends ServiceEntityRepository
{
    /**
     * ListingIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ListingIntl::class);
    }
}
