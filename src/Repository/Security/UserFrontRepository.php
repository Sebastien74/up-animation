<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Core\Website;
use App\Entity\Security\UserFront;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UserFrontRepository.
 *
 * @extends ServiceEntityRepository<UserFront>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserFrontRepository extends ServiceEntityRepository
{
    /**
     * UserFrontRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, UserFront::class);
    }

    /**
     * Find User by identifier.
     *
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?UserFront
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('u.login = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find users with token not NULL.
     */
    public function findHaveToken()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.token IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find User for switcher selector.
     *
     * @return array<UserFront>
     */
    public function findForSwitcher(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.login', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find User[] by role.
     *
     * @return array<UserFront>
     */
    public function findByWebsiteAndRole(Website $website, string $role): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.group', 'g')
            ->leftJoin('g.roles', 'r')
            ->andWhere('u.website = :website')
            ->andWhere('r.name = :name')
            ->setParameter('website', $website)
            ->setParameter('name', $role)
            ->addSelect('g')
            ->addSelect('r')
            ->getQuery()
            ->getResult();
    }

    /**
     * To get all expired UserFront[].
     */
    public function findWithExpiredToken(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.tokenDate IS NOT NULL')
            ->andWhere('u.tokenDate < :dateLimit')
            ->andWhere('u.active = :active')
            ->setParameter('dateLimit', new \DateTimeImmutable('-24 hours'))
            ->setParameter('active', false)
            ->getQuery()
            ->getResult();
    }
}
