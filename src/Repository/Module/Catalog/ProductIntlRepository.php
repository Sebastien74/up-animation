<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\ProductIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ProductIntlRepository.
 *
 * @extends ServiceEntityRepository<ProductIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ProductIntlRepository extends ServiceEntityRepository
{
    /**
     * ProductIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ProductIntl::class);
    }
}
