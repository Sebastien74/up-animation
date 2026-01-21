<?php

declare(strict_types=1);

namespace App\Repository\Translation;

use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationDomain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TranslationRepository.
 *
 * @extends ServiceEntityRepository<Translation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TranslationRepository extends ServiceEntityRepository
{
    /**
     * TranslationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Translation::class);
    }

    /**
     * Find by TranslationDomain & content.
     *
     * @return array<Translation>
     */
    public function findByDomainAndContent(TranslationDomain $domain, string $content, string $locale): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.unit', 'u')
            ->andWhere('u.domain = :domain')
            ->andWhere('t.content = :content')
            ->andWhere('t.locale = :locale')
            ->setParameter('domain', $domain)
            ->setParameter('content', $content)
            ->setParameter('locale', $locale)
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find one by TranslationDomain & content.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByDomainAndKeyName(TranslationDomain $domain, string $keyName, string $locale): ?Translation
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.unit', 'u')
            ->andWhere('u.domain = :domain')
            ->andWhere('u.keyName = :keyName')
            ->andWhere('t.locale = :locale')
            ->setParameter('domain', $domain)
            ->setParameter('keyName', $keyName)
            ->setParameter('locale', $locale)
            ->addSelect('u')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by TranslationDomain & content.
     *
     * @return array<Translation>
     */
    public function findByDomainAndKeyName(TranslationDomain $domain, string $keyName, string $locale): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.unit', 'u')
            ->andWhere('u.domain = :domain')
            ->andWhere('u.keyName = :keyName')
            ->andWhere('t.locale = :locale')
            ->setParameter('domain', $domain)
            ->setParameter('keyName', $keyName)
            ->setParameter('locale', $locale)
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get Translations by locale.
     */
    public function findByLocale(string $locale): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.unit', 'u')
            ->leftJoin('u.domain', 'd')
            ->andWhere('t.content IS NOT NULL')
            ->addSelect('u')
            ->addSelect('d')
            ->getQuery()
            ->getResult();
    }
}
