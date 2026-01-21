<?php

declare(strict_types=1);

namespace App\Repository\Module\Table;

use App\Entity\Core\Website;
use App\Entity\Module\Table\Table;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TableRepository.
 *
 * @extends ServiceEntityRepository<Table>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TableRepository extends ServiceEntityRepository
{
    /**
     * TableRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Table::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Table
    {
        $statement = $this->createQueryBuilder('t')
            ->leftJoin('t.website', 'w')
            ->leftJoin('t.cols', 'c')
            ->leftJoin('c.cells', 'cl')
            ->leftJoin('cl.intls', 'i')
            ->andWhere('t.website = :website')
            ->setParameter('website', $website)
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('cl')
            ->addSelect('i');

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
