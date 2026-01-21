<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\SubCategoryIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SubCategoryIntlRepository.
 *
 * @extends ServiceEntityRepository<SubCategoryIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SubCategoryIntlRepository extends ServiceEntityRepository
{
    /**
     * SubCategoryIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SubCategoryIntl::class);
    }
}
