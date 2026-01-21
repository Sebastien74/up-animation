<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\ConfigurationMediaRelation;
use App\Entity\Core\Website;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ConfigurationMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<ConfigurationMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ConfigurationMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * ConfigurationMediaRelation constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, ConfigurationMediaRelation::class);
    }

    /**
     * Get default locale media.
     *
     * @throws NonUniqueResultException
     */
    public function findDefaultLocaleCategory(Website $website, string $category, string $locale): ?ConfigurationMediaRelation
    {
        return $this->createQueryBuilder('mr')
            ->leftJoin('mr.media', 'm')
            ->andWhere('mr.locale = :locale')
            ->andWhere('mr.categorySlug = :category')
            ->andWhere('m.website = :website')
            ->setParameter('locale', $locale)
            ->setParameter('category', $category)
            ->setParameter('website', $website)
            ->addSelect('m')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
