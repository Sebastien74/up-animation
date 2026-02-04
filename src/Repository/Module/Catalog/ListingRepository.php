<?php

declare(strict_types=1);

namespace App\Repository\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ListingRepository.
 *
 * @extends ServiceEntityRepository<Listing>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ListingRepository extends ServiceEntityRepository
{
    private array $cache = [];

    /**
     * ListingRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Listing::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Listing
    {
        $statement = $this->createQueryBuilder('l');

        if (is_numeric($filter)) {
            $statement->andWhere('l.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('l.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find by slugs.
     *
     * @throws NonUniqueResultException
     */
    public function findBySlugs(array $slugs = []): array
    {
        if (array_key_exists('findBySlugs', $this->cache)) {
            return $this->cache['findBySlugs'];
        }

        $result = !empty($slugs) ? $this->createQueryBuilder('l')
            ->andWhere('l.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->getResult() : [];

        $listings = [];
        foreach ($result as $item) {
            $listings[$item->getSlug()] = $item;
        }

        $this->cache['findBySlugs'] = $listings;

        return $listings;
    }

    /**
     * Save.
     */
    public function save(Listing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove.
     */
    public function remove(Listing $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
