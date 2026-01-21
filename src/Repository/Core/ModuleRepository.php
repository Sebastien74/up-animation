<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ModuleRepository.
 *
 * @extends ServiceEntityRepository<Module>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModuleRepository extends ServiceEntityRepository
{
    private array $cache = [];

    /**
     * ModuleRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Module::class);
    }

    /**
     * Find one by slug.
     *
     * @throws NonUniqueResultException
     */
    public function findOneBySlug(string $slug): ?Module
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find intls[].
     */
    public function findForIntls(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m')
            ->leftJoin('m.intls', 'i')
            ->addSelect('i')
            ->getQuery()
            ->getResult();
    }
}
