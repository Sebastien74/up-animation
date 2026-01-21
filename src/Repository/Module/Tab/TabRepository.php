<?php

declare(strict_types=1);

namespace App\Repository\Module\Tab;

use App\Entity\Core\Website;
use App\Entity\Module\Tab\Tab;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TabRepository.
 *
 * @extends ServiceEntityRepository<Tab>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TabRepository extends ServiceEntityRepository
{
    /**
     * TabRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Tab::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Tab
    {
        $statement = $this->createQueryBuilder('t')
            ->leftJoin('t.website', 'w')
            ->leftJoin('t.contents', 'c')
            ->leftJoin('c.intls', 'i')
            ->leftJoin('c.mediaRelations', 'mr')
            ->leftJoin('mr.media', 'mrm')
            ->andWhere('t.website = :website')
            ->setParameter('website', $website)
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('i')
            ->addSelect('mr')
            ->addSelect('mrm');

        if (is_numeric($filter)) {
            $statement->andWhere('t.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('t.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }
}
