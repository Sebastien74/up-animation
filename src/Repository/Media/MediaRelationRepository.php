<?php

declare(strict_types=1);

namespace App\Repository\Media;

use App\Entity\Core\Website;
use App\Entity\Media\MediaRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MediaRelationRepository.
 *
 * @extends ServiceEntityRepository<MediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaRelationRepository extends ServiceEntityRepository
{
    /**
     * MediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, MediaRelation::class);
    }

    /**
     * Find by WebsiteModel cache generated iterable.
     */
    public function findByWebsiteAndAsCache(Website $website): array
    {
        return $this->createQueryBuilder('mr')
            ->leftJoin('mr.media', 'm')
            ->andWhere('mr.cacheDate IS NOT NULL')
            ->andWhere('m.website = :website')
            ->setParameter('website', $website)
            ->addSelect('m')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find by WebsiteModel cache generated iterable.
     *
     * @return array<MediaRelation>
     */
    public function findOneByWebsiteAndName(Website $website, string $filename, string $extension, bool $last = false): array
    {
        $filename = str_replace('.webp', '', $filename);

        $results = $this->createQueryBuilder('mr')
            ->leftJoin('mr.media', 'm')
            ->andWhere('mr.cacheDate IS NOT NULL')
            ->andWhere('m.website = :website')
            ->andWhere('m.filename = :filename')
            ->setParameter('website', $website)
            ->setParameter('filename', $filename)
            ->addSelect('m')
            ->getQuery()
            ->getResult();

        if (!$results && !$last) {
            $filename = str_replace('.'.$extension, '', $filename);
            $matches = explode('.', $filename);
            $filename = str_replace('.'.end($matches), '', $filename).'.'.$extension;
            $results = $this->findOneByWebsiteAndName($website, $filename, $extension, true);
        }

        return $results;
    }

    /**
     * Get unused MediaRelation[].
     *
     * @return array<MediaRelation>
     */
    public function findUnused(): array
    {
        return $this->createQueryBuilder('mr')
            ->leftJoin('mr.media', 'm')
            ->andWhere('m.screen = :screen')
            ->andWhere('m.filename IS NULL')
            ->setParameter('screen', 'desktop')
            ->addSelect('m')
            ->getQuery()
            ->getResult();
    }
}
