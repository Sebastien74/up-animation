<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\UserCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UserCategory.
 *
 * @extends ServiceEntityRepository<UserCategory>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserCategoryRepository extends ServiceEntityRepository
{
    /**
     * UserCategoryRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, UserCategory::class);
    }

    /**
     * Find by intlSlug.
     *
     * @throws NonUniqueResultException
     */
    public function findBySlug(string $locale, string $slug = null): ?UserCategory
    {
        if (!$slug) {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->leftJoin('u.intls', 'i')
            ->andWhere('i.locale = :locale')
            ->andWhere('i.slug = :slug')
            ->setParameter('locale', $locale)
            ->setParameter('slug', $slug)
            ->addSelect('i')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
