<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\CatalogIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CatalogIntlRepository.
 *
 * @extends ServiceEntityRepository<CatalogIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CatalogIntlRepository extends ServiceEntityRepository
{
    /**
     * CatalogIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CatalogIntl::class);
    }
}
