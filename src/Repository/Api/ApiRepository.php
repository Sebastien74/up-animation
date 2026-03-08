<?php

declare(strict_types=1);

namespace App\Repository\Api;

use App\Entity\Api\Api;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ApiRepository.
 *
 * @extends ServiceEntityRepository<Api>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ApiRepository extends ServiceEntityRepository
{
    /**
     * ApiRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Api::class);
    }

    /**
     * Find by id.
     *
     * @throws NonUniqueResultException
     */
    public function findObjectByLocale(int $id, string $locale): ?Api
    {
        $api = $this->createQueryBuilder('a')
            ->innerJoin('a.facebook', 'f')
            ->innerJoin('a.google', 'g')
            ->innerJoin('a.instagram', 'i')
            ->innerJoin('a.custom', 'c')
            ->innerJoin('f.intls', 'fi')
            ->innerJoin('g.intls', 'gi')
            ->innerJoin('c.intls', 'ci')
            ->andWhere('a.id = :id')
            ->andWhere('fi.locale = :locale')
            ->andWhere('gi.locale = :locale')
            ->andWhere('ci.locale = :locale')
            ->setParameter('id', $id)
            ->setParameter('locale', $locale)
            ->addSelect('f')
            ->addSelect('g')
            ->addSelect('i')
            ->addSelect('c')
            ->addSelect('fi')
            ->addSelect('gi')
            ->addSelect('ci')
            ->getQuery()
            ->enableResultCache(3600, 'api-'.$id.'-'.$locale)
            ->getOneOrNullResult();

        if (!$api) {
            $api = $this->createQueryBuilder('a')
                ->andWhere('a.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->enableResultCache(3600, 'api-simple-'.$id)
                ->getOneOrNullResult();
        }

        return $api;
    }
}
