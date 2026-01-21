<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\InvalidArgumentException;

/**
 * UserRepository.
 *
 * @extends ServiceEntityRepository<User>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * UserRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, User::class);
    }

    /**
     * Find User by identifier.
     *
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('u.login = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find User Email order alphabetical.
     *
     * @return array<User>
     */
    public function findAllEmailAlphabetical(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->execute();
    }

    /**
     * Find user by email LIKE.
     *
     * @return array<User>
     */
    public function findAllMatching(string $query, int $limit = 5): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find User for switcher selector.
     *
     * @return array<User>
     * @throws InvalidArgumentException
     */
    public function findForSwitcher(): array
    {
        $excluded = ['ROLE_INTERNAL'];

        $users = $this->createQueryBuilder('u')
            ->orderBy('u.login', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($users as $key => $user) {
            /** @var User $user */
            foreach ($user->getRoles() as $role) {
                if (in_array($role, $excluded)) {
                    unset($users[$key]);
                }
            }
        }

        return $users;
    }

    /**
     * Find user by login or email.
     *
     * @throws NonUniqueResultException
     */
    public function findExisting(string $login, string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.login = :login')
            ->orWhere('u.email = :email')
            ->setParameter('login', $login)
            ->setParameter('email', $email)
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
            ->andWhere('u.tokenRequest IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
