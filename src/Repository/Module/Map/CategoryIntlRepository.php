<?php

declare(strict_types=1);

namespace App\Repository\Module\Map;

use App\Entity\Module\Map\CategoryIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CategoryIntlRepository.
 *
 * @extends ServiceEntityRepository<CategoryIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryIntlRepository extends ServiceEntityRepository
{
    /**
     * CategoryIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CategoryIntl::class);
    }
}
