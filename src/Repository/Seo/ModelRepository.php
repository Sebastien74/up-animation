<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\Model;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ModelRepository.
 *
 * @extends ServiceEntityRepository<Model>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModelRepository extends ServiceEntityRepository
{
    /**
     * ModelRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Model::class);
    }

    /**
     * Find Url as array.
     */
    public function findByLocaleClassnameAndWebsite(
        string $locale,
        string $classname,
        Website $website,
        ?string $childClassName = null,
        ?int $entityId = null): ?Model
    {
        $classname = str_replace('Proxies\__CG__\\', '', $classname);

        $result = $this->createQueryBuilder('m')
            ->andWhere('m.locale = :locale')
            ->andWhere('m.className = :className')
            ->andWhere('m.website = :website')
            ->setParameter('locale', $locale)
            ->setParameter('className', $classname)
            ->setParameter('website', $website->getId());

        if ($childClassName && $entityId) {
            $result = $result->andWhere('m.childClassName = :childClassName')
                ->andWhere('m.entityId= :entityId')
                ->setParameter('childClassName', $childClassName)
                ->setParameter('entityId', $entityId);
        }

        $result = $result->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $result;
    }
}
