<?php

declare(strict_types=1);

namespace App\Repository\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\NotFoundUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * NotFoundUrlRepository.
 *
 * @extends ServiceEntityRepository<NotFoundUrl>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NotFoundUrlRepository extends ServiceEntityRepository
{
    /**
     * NotFoundUrlRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, NotFoundUrl::class);
    }

    /**
     * Find front NotFoundUrl without redirections.
     */
    public function findFrontWithoutRedirections(Website $website, array $domains, int $limit = 50): array
    {
        $queryBuilder = $this->createQueryBuilder('n')
            ->andWhere('n.website = :website')
            ->andWhere('n.category = :category')
            ->andWhere('n.type = :type')
            ->andWhere('n.haveRedirection = :haveRedirection')
            ->setParameter('website', $website)
            ->setParameter('category', 'url')
            ->setParameter('type', 'front')
            ->setParameter('haveRedirection', false);
        $query = '';
        foreach ($domains as $key => $domain) {
            $query .= 'n.url LIKE :haveRedirection_'.$key.' OR ';
            $queryBuilder->setParameter('haveRedirection_'.$key, '%'.$domain->getName().'%');
        }
        $query = rtrim($query, ' OR');
        if ($query) {
            $queryBuilder->andWhere($query);
        }
        $queryBuilder = $queryBuilder
            ->setMaxResults($limit)
            ->getQuery();

        $result = $queryBuilder->getResult();
        if ($result >= $limit && $limit > 1) {
            $others = $this->findFrontWithoutRedirections($website, $domains, 1);
            $result = array_merge($result, $others);
        }

        return $result;
    }

    /**
     * Find by category and type.
     */
    public function findByCategoryTypeQuery(Website $website, string $category, string $type): Query
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.website = :website')
            ->andWhere('n.category = :category')
            ->andWhere('n.type = :type')
            ->andWhere('n.haveRedirection = :haveRedirection')
            ->setParameter('website', $website)
            ->setParameter('category', $category)
            ->setParameter('type', $type)
            ->setParameter('haveRedirection', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery();
    }
}
