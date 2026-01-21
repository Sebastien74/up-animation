<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\FeatureValueIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FeatureValueIntlRepository.
 *
 * @extends ServiceEntityRepository<FeatureValueIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureValueIntlRepository extends ServiceEntityRepository
{
    /**
     * FeatureValueIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FeatureValueIntl::class);
    }
}
