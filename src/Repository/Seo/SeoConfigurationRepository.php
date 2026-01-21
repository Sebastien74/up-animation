<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\SeoConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SeoConfigurationRepository.
 *
 * @extends ServiceEntityRepository<SeoConfiguration>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SeoConfigurationRepository extends ServiceEntityRepository
{
    /**
     * SeoConfigurationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SeoConfiguration::class);
    }

    /**
     * Find by Website.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByWebsite(Website $website): ?SeoConfiguration
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.website', 'w')
            ->andWhere('w.id = :id')
            ->setParameter('id', $website->getId())
            ->addSelect('w')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
