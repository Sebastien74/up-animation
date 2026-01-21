<?php

declare(strict_types=1);

namespace App\Repository\Translation;

use App\Entity\Translation\TranslationDomain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TranslationDomainRepository.
 *
 * @extends ServiceEntityRepository<TranslationDomain>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TranslationDomainRepository extends ServiceEntityRepository
{
    private const FRONT_DOMAINS = [
        'front_default',
        'front',
        'front_form',
        'front_js_plugins',
        'gdpr',
        'build',
        'ie_alert',
    ];

    /**
     * TranslationDomainRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, TranslationDomain::class);
    }

    /**
     * Get All Domains.
     *
     * @return array<TranslationDomain>
     */
    public function getFrontDomains(): array
    {
        return self::FRONT_DOMAINS;
    }

    /**
     * Get All Domains.
     *
     * @return array<TranslationDomain>
     */
    public function findAllDomains(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.units', 'u')
            ->leftJoin('u.translations', 't')
            ->orderBy('d.adminName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get DomainModel.
     *
     * @throws NonUniqueResultException
     */
    public function findDomain(int $id): ?TranslationDomain
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.units', 'u')
            ->leftJoin('u.translations', 't')
            ->andWhere('d.id = :id')
            ->setParameter('id', $id)
            ->addSelect('u')
            ->addSelect('t')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get TranslationDomain[] by names.
     */
    public function findFront(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.units', 'u')
            ->leftJoin('u.translations', 't')
            ->andWhere('d.name IN (:names)')
            ->setParameter('names', self::FRONT_DOMAINS)
            ->orderBy('d.adminName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get TranslationDomain[] by names.
     */
    public function findAdmin(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.units', 'u')
            ->leftJoin('u.translations', 't')
            ->andWhere('d.name NOT IN (:names)')
            ->setParameter('names', self::FRONT_DOMAINS)
            ->orderBy('d.adminName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get Domains by search.
     */
    public function findBySearch(?string $search = null): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.units', 'u')
            ->leftJoin('u.translations', 't')
            ->andWhere('u.keyName LIKE :search')
            ->orWhere('t.content LIKE :search')
            ->setParameter('search', '%'.trim($search).'%')
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }
}
