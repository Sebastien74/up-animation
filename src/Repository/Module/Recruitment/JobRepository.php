<?php

declare(strict_types=1);

namespace App\Repository\Module\Recruitment;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Recruitment\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * JobRepository.
 *
 * @extends ServiceEntityRepository<Job>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class JobRepository extends ServiceEntityRepository
{
    /**
     * JobRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Job::class);
    }

    /**
     * Find online by Website and locale.
     *
     * @return array<Product>
     */
    public function findOnlineByWebsiteAndLocale(Website $website, string $locale): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.website', 'w')
            ->leftJoin('j.urls', 'u')
            ->andWhere('j.website = :website')
            ->andWhere('j.publicationStart IS NULL OR j.publicationStart < CURRENT_TIMESTAMP()')
            ->andWhere('j.publicationEnd IS NULL OR j.publicationEnd > CURRENT_TIMESTAMP()')
            ->andWhere('j.publicationStart IS NOT NULL')
            ->andWhere('u.locale = :locale')
            ->andWhere('u.online = :online')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->setParameter('online', true)
            ->addSelect('w')
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    /**
     * Save.
     */
    public function save(Job $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Job $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
