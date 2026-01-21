<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\BlockMediaRelation;
use App\Entity\Layout\Layout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlockMediaRelationRepository.
 *
 * @extends ServiceEntityRepository<BlockMediaRelation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockMediaRelationRepository extends ServiceEntityRepository
{
    /**
     * BlockMediaRelationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, BlockMediaRelation::class);
    }

    /**
     * Find for Tree position.
     *
     * @return array<BlockMediaRelationRepository>
     */
    public function findWithEmptyAlt(Layout $layout): array
    {
        $mediaRelations = $this->createQueryBuilder('mr')
            ->leftJoin('mr.intl', 'i')
            ->leftJoin('mr.media', 'm')
            ->leftJoin('mr.block', 'b')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('i.placeholder IS NULL')
            ->andWhere('m.filename IS NOT NULL')
            ->andWhere('l.id = :layout')
            ->setParameter('layout', $layout->getId())
            ->addSelect('i')
            ->addSelect('m')
            ->addSelect('b')
            ->addSelect('c')
            ->addSelect('z')
            ->addSelect('l')
            ->getQuery()
            ->getResult();

        $response = [];
        foreach ($mediaRelations as $mediaRelation) {
            $blockId = $mediaRelation->getBlock()->getId();
            $filename = $mediaRelation->getMedia()->getFilename();
            $locales = !empty($response[$filename][$blockId]['locales']) ? $response[$filename][$blockId]['locales'] : [];
            $locales[] = $mediaRelation->getLocale();
            $response[$filename][$blockId] = [
                'locales' => $locales,
                'block' => $mediaRelation->getBlock(),
            ];
        }

        return $response;
    }
}
