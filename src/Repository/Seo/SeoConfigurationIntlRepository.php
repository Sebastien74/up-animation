<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Seo\SeoConfigurationIntl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SeoConfigurationIntlRepository.
 *
 * @extends ServiceEntityRepository<SeoConfigurationIntl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeoConfigurationIntlRepository extends ServiceEntityRepository
{
    /**
     * SeoConfigurationIntlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SeoConfigurationIntl::class);
    }
}
