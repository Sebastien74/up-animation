<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Module\Catalog\FeatureIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * FeatureIntlRepository.
 *
 * @extends ServiceEntityRepository<FeatureIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureIntlRepository extends ServiceEntityRepository
{
    /**
     * FeatureIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, FeatureIntl::class);
    }
}
