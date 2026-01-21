<?php

declare(strict_types=1);

namespace App\Repository\Module\Map;

use App\Entity\Core\Website;
use App\Entity\Module\Map\Map;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MapRepository.
 *
 * @extends ServiceEntityRepository<Map>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MapRepository extends ServiceEntityRepository
{
    /**
     * MapRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Map::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Map
    {
        $statement = $this->createQueryBuilder('m')
            ->leftJoin('m.website', 'w')
            ->leftJoin('m.points', 'p')
            ->leftJoin('p.categories', 'c')
            ->leftJoin('c.intls', 'ci')
            ->leftJoin('p.address', 'a')
            ->leftJoin('p.intls', 'pi')
            ->andWhere('m.website = :website')
            ->setParameter('website', $website)
            ->addSelect('w')
            ->addSelect('p')
            ->addSelect('c')
            ->addSelect('ci')
            ->addSelect('a')
            ->addSelect('pi');

        if (is_numeric($filter)) {
            $statement->andWhere('m.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('m.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find default.
     */
    public function findDefault(int $websiteId): array
    {
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.website', 'w')
            ->leftJoin('m.points', 'p')
            ->leftJoin('p.categories', 'c')
            ->leftJoin('c.intls', 'ci')
            ->leftJoin('p.address', 'a')
            ->leftJoin('p.intls', 'pi')
            ->leftJoin('p.phones', 'pp')
            ->andWhere('m.website = :website')
            ->andWhere('m.asDefault = :asDefault')
            ->setParameter('website', $websiteId)
            ->setParameter('asDefault', true)
            ->addSelect('w')
            ->addSelect('p')
            ->addSelect('c')
            ->addSelect('ci')
            ->addSelect('a')
            ->addSelect('pp')
            ->addSelect('pi')
            ->getQuery()
            ->getArrayResult();

        return !empty($result[0]) ? $result[0] : [];
    }
}
