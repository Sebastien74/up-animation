<?php

declare(strict_types=1);

namespace App\Repository\Module\Gallery;

use App\Entity\Core\Website;
use App\Entity\Module\Gallery\Gallery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GalleryRepository.
 *
 * @extends ServiceEntityRepository<Gallery>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GalleryRepository extends ServiceEntityRepository
{
    /**
     * GalleryRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Gallery::class);
    }

    /**
     * Find one by filter.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByFilter(Website $website, string $locale, mixed $filter): ?Gallery
    {
        $statement = $this->createQueryBuilder('g')
            ->leftJoin('g.website', 'w')
            ->leftJoin('g.category', 'c')
            ->leftJoin('g.mediaRelations', 'mr')
            ->leftJoin('mr.intl', 'mri')
            ->leftJoin('mr.media', 'm')
            ->leftJoin('m.intls', 'mi')
            ->andWhere('g.website = :website')
            ->andWhere('mr.locale = :locale')
            ->setParameter('website', $website)
            ->setParameter('locale', $locale)
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('mr')
            ->addSelect('mri')
            ->addSelect('m')
            ->addSelect('mi');

        if (is_numeric($filter)) {
            $statement->andWhere('g.id = :id')
                ->setParameter('id', $filter);
        } else {
            $statement->andWhere('g.slug = :slug')
                ->setParameter('slug', $filter);
        }

        return $statement->getQuery()
            ->getOneOrNullResult();
    }
}
