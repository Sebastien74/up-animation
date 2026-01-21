<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\Url;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UrlRepository.
 *
 * @extends ServiceEntityRepository<Url>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UrlRepository extends ServiceEntityRepository
{
    /**
     * UrlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Url::class);
    }

    /**
     * Find Url as array.
     */
    public function findArray(int $id): array
    {
        $result = $this->defaultJoin($this->createQueryBuilder('u'))
            ->andWhere('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setMaxResults(1)
            ->getArrayResult();

        return !empty($result[0]) ? $result[0] : [];
    }

    /**
     * Find Empty SEO.
     *
     * @throws NoResultException|NonUniqueResultException
     */
    public function countEmptyLocalesSEO(Website $website): array
    {
        $counts = [];
        $counts['total'] = 0;

        foreach ($website->getConfiguration()->getAllLocales() as $locale) {
            $result = $this->createQueryBuilder('u')
                ->select('count(u.id)')
                ->leftJoin('u.seo', 's')
                ->andWhere('u.website = :website')
                ->andWhere('u.locale = :locale')
                ->andWhere('u.online = :online')
                ->andWhere('s.metaTitle IS NULL OR s.metaDescription IS NULL')
                ->setParameter('website', $website)
                ->setParameter('locale', $locale)
                ->setParameter('online', true)
                ->getQuery();
            $result = $result->getSingleScalarResult();
            $counts[$locale] = intval($result);
            $counts['total'] = $counts['total'] + $counts[$locale];
        }

        return $counts;
    }

    /**
     * Get default Join.
     */
    private function defaultJoin(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->leftJoin('u.website', 'w')
            ->leftJoin('u.seo', 's')
            ->leftJoin('u.indexPage', 'up')
            ->leftJoin('s.mediaRelation', 'mr')
            ->leftJoin('mr.media', 'm')
            ->leftJoin('w.configuration', 'c')
            ->leftJoin('w.information', 'i')
            ->leftJoin('w.seoConfiguration', 'sc')
            ->leftJoin('i.intls', 'ii')
            ->addSelect('w')
            ->addSelect('up')
            ->addSelect('s')
            ->addSelect('mr')
            ->addSelect('m')
            ->addSelect('c')
            ->addSelect('i')
            ->addSelect('sc')
            ->addSelect('ii');
    }
}
