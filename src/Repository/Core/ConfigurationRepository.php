<?php

declare(strict_types=1);

namespace App\Repository\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Entity\Layout\BlockType;
use App\Model\Core\WebsiteModel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ConfigurationRepository.
 *
 * @extends ServiceEntityRepository<Configuration>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    private array $cache = [];

    /**
     * ConfigurationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Configuration::class);
    }

    public function findById(int $id): ?Configuration
    {
        if (array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }
        $configuration = $this->findOneBy(['id' => $id]);
        $this->cache[$id] = $configuration;

        return $configuration;
    }

    /**
     * Get ConfigurationModel optimized query.
     *
     * @throws NonUniqueResultException
     */
    public function findOptimizedAdmin(WebsiteModel $website, string $locale): ?Configuration
    {
        return $this->createQueryBuilder('c')->select('c')
            ->leftJoin('c.website', 'w')
            ->leftJoin('c.mediaRelations', 'mr')
            ->andWhere('w.id = :website')
            ->andWhere('mr.locale = :locale')
            ->setParameter('website', $website->id)
            ->setParameter('locale', $locale)
            ->addSelect('w')
            ->addSelect('mr')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get MediaRelation[] ConfigurationModel.
     *
     * @throws NonUniqueResultException
     */
    public function findMediaRelations(?int $configurationId = null, ?string $category = null, ?string $locale = null): PersistentCollection|array
    {
        if (!$configurationId) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('c')->select('c')
            ->leftJoin('c.mediaRelations', 'mediaRelations')
            ->leftJoin('mediaRelations.media', 'media')
            ->andWhere('c.id = :id')
            ->andWhere('mediaRelations.media IS NOT NULL')
            ->setParameter('id', $configurationId)
            ->addSelect('mediaRelations')
            ->addSelect('media');

        if ($locale) {
            $queryBuilder->andWhere('mediaRelations.locale = :locale')
                ->setParameter('locale', $locale);
        }

        if ($category) {
            $queryBuilder->andWhere('media.category = :category')
                ->setParameter('category', $category);
        }

        $queryBuilder = $queryBuilder->getQuery();
        $configuration = $queryBuilder->getOneOrNullResult();

        return $configuration ? $configuration->getMediaRelations() : [];
    }

    /**
     * Get BlockTypes by categories.
     *
     * @throws NonUniqueResultException
     */
    public function findBlocksTypes(Website $website, $categories): PersistentCollection|array
    {
        $qb = $this->createQueryBuilder('c')->select('c')
            ->join('c.blockTypes', 'b')
            ->join('c.website', 'w')
            ->andWhere('w.id = :website')
            ->setParameter('website', $website)
            ->addSelect('w')
            ->addSelect('b');

        foreach ($categories as $key => $category) {
            $qb->orWhere('b.category = :category'.$key);
            $qb->setParameter(':category'.$key, $category);
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? $result->getBlockTypes() : [];
    }

    /**
     * Check if Website Module existing.
     */
    public function moduleExist(Website $website, ?Module $module = null, bool $object = false): bool|Module
    {
        if (!$module) {
            return false;
        }
        foreach ($website->getConfiguration()->getModules() as $moduleDb) {
            if ($moduleDb->getId() === $module->getId()) {
                return $object ? $module : true;
            }
        }

        return false;
    }

    /**
     * Check if Website BlockType existing.
     */
    public function blockTypeExist(Website $website, ?BlockType $blockType = null, bool $object = false): bool|BlockType
    {
        if (!$blockType) {
            return false;
        }
        foreach ($website->getConfiguration()->getBlockTypes() as $blockTypeDb) {
            if ($blockTypeDb->getId() === $blockType->getId()) {
                return $object ? $blockType : true;
            }
        }

        return false;
    }
}
