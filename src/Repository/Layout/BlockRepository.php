<?php

declare(strict_types=1);

namespace App\Repository\Layout;

use App\Entity\Layout\Block;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlockRepository.
 *
 * @extends ServiceEntityRepository<Block>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockRepository extends ServiceEntityRepository
{
    private array $cache = [];

    /**
     * BlockRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Block::class);
    }

//    /**
//     * Find by I'd.
//     *
//     * @throws NonUniqueResultException
//     */
//    public function findById(int $id): ?Block
//    {
//        if (isset($this->cache['id'][$id])) {
//            return $this->cache['id'][$id];
//        }
//
//        $result = $this->createQueryBuilder('b')
//            ->leftJoin('b.intls', 'i')
//            ->andWhere('b.id = :id')
//            ->setParameter('id', $id)
//            ->addSelect('i')
//            ->getQuery()
//            ->enableResultCache(3600, 'block_id_'.$id)
//            ->getOneOrNullResult();
//
//        return $this->cache['id'][$id] = $result;
//    }

    /**
     * Find Block by titleForce, locale & Page.
     */
    public function findTitleByForceAndLocalePage(mixed $entity, string $locale, ?int $titleForce = null, bool $all = false): array|string|null
    {
        $blocks = $this->findAllTitlesByForceAndLocale($locale, $titleForce);
        $layoutId = $entity->getLayout()->getId();
        $layoutBlocks = !empty($blocks[$layoutId]) ? $blocks[$layoutId] : null;

        if ($layoutBlocks) {
            if ($all) {
                return $layoutBlocks;
            }
            return !empty($layoutBlocks[0]['title']) ? $layoutBlocks[0]['title'] : null;
        }

        return null;
    }

    /**
     * Find all blocks with a title.
     */
    public function findAllTitlesByForceAndLocale(string $locale, ?int $titleForce = 1): array|string|null
    {
        $cacheKey = 'allTitleForce'.$titleForce;

        if (isset($this->cache[$cacheKey][$locale])) {
            return $this->cache[$cacheKey][$locale];
        }

        $rows = $this->createQueryBuilder('b')
            ->select('l.id AS layoutId', 'b.id AS blockId', 'i.title AS title')
            ->innerJoin('b.intls', 'i')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('i.titleForce = :titleForce')
            ->andWhere('i.title IS NOT NULL')
            ->andWhere('i.locale = :locale')
            ->setParameter('titleForce', $titleForce)
            ->setParameter('locale', $locale)
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $layoutId = (int) $row['layoutId'];
            $result[$layoutId][] = [
                'blockId' => (int) $row['blockId'],
                'title' => $row['title'],
            ];
        }

        return $this->cache[$cacheKey][$locale] = $result;
    }

    /**
     * Find the block by titleForce, locale & Layout.
     */
    public function findTitleByForceAndLocaleLayout(mixed $layout, string $locale, int $titleForce, bool $all = false): mixed
    {
        $layoutId = is_array($layout) ? $layout['id'] : $layout->getId();
        $cacheKey = $layoutId . '-' . $locale . '-' . $titleForce . '-' . ($all ? 'all' : 'one');
        if (isset($this->cache['title_layout'][$cacheKey])) {
            return $this->cache['title_layout'][$cacheKey];
        }

        $results = $this->createQueryBuilder('b')
            ->leftJoin('b.intls', 'i')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('i.titleForce = :titleForce')
            ->andWhere('i.title IS NOT NULL')
            ->andWhere('i.locale = :locale')
            ->andWhere('l.id = :layoutId')
            ->setParameter('titleForce', $titleForce)
            ->setParameter('locale', $locale)
            ->setParameter('layoutId', $layoutId)
            ->addSelect('i')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->enableResultCache(3600, 'block_title_layout_'.md5($cacheKey))
            ->getResult();

        /** @var Block $result */
        $result = $results ? $results[0] : null;

        $intlResult = null;
        if (is_object($result) && method_exists($result, 'getIntls')) {
            foreach ($result->getIntls() as $intl) {
                if ($locale === $intl->getLocale()) {
                    $intlResult = $intl;
                    break;
                }
            }
        }

        $finalResult = null;
        if ($result && $all) {
            $finalResult = $intlResult;
        } elseif ($intlResult) {
            $finalResult = $intlResult->getTitle();
        }

        return $this->cache['title_layout'][$cacheKey] = $finalResult;
    }

    /**
     * Find block by titleForce, locale & Page.
     */
    public function findByBlockTypeAndLocaleLayout(mixed $layout, string $blockType, string $locale, array $options = []): mixed
    {
        $layoutId = is_object($layout) ? $layout->getId() : $layout['id'];
        $asThumb = $options['asThumb'] ?? false;
        $haveContent = $options['haveContent'] ?? false;

        $cacheKey = $layoutId . '-' . $blockType . '-' . $locale . '-' . ($asThumb ? 'thumb' : 'no_thumb') . '-' . ($haveContent ? 'content' : 'no_content');
        if (isset($this->cache['block_type'][$cacheKey])) {
            return $this->cache['block_type'][$cacheKey];
        }

        $statement = $this->createQueryBuilder('b')
            ->leftJoin('b.blockType', 'bt')
            ->leftJoin('b.intls', 'i')
//            ->leftJoin('b.mediaRelations', 'bmr')
//            ->leftJoin('bmr.media', 'bmrm')
//            ->leftJoin('b.actionIntls', 'bai')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('bt.slug = :slug')
            ->andWhere('i.locale = :locale')
            ->andWhere('l.id = :layoutId')
            ->setParameter('slug', $blockType)
            ->setParameter('locale', $locale)
            ->setParameter('layoutId', $layoutId)
            ->addSelect('bt')
            ->addSelect('i')
//            ->addSelect('bmr')
//            ->addSelect('bmrm')
//            ->addSelect('bai')
            ->addSelect('c')
            ->addSelect('z')
            ->addSelect('l')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC');

        if ($haveContent && ('title' === $blockType || 'title-header' === $blockType)) {
            $statement->andWhere('i.title IS NOT NULL');
        } elseif ($haveContent) {
            $statement->andWhere('i.body IS NOT NULL OR i.introduction IS NOT NULL');
        }

        $blocks = $statement->getQuery()
            ->enableResultCache(3600, 'block_type_'.md5($cacheKey))
            ->getResult();

        $result = !empty($blocks[0]) ? $blocks[0] : null;

        if ($asThumb) {
            foreach ($blocks as $block) {
                /** @var Block $block */
                if ($block->isUseForThumb()) {
                    $result = $block;
                    break;
                }
            }
        }

        return $this->cache['block_type'][$cacheKey] = $result;
    }

    /**
     * Batch variant of findByBlockTypeAndLocaleLayout, keyed by layout id.
     *
     * @param int[] $layoutIds
     *
     * @return array<int, Block|null>
     */
    public function findByBlockTypeAndLayouts(array $layoutIds, string $blockType, string $locale, array $options = []): array
    {
        if (!$layoutIds) {
            return [];
        }
        $asThumb = $options['asThumb'] ?? false;
        $haveContent = $options['haveContent'] ?? false;

        $statement = $this->createQueryBuilder('b')
            ->leftJoin('b.blockType', 'bt')
            ->leftJoin('b.intls', 'i')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('bt.slug = :slug')
            ->andWhere('i.locale = :locale')
            ->andWhere('l.id IN (:layoutIds)')
            ->setParameter('slug', $blockType)
            ->setParameter('locale', $locale)
            ->setParameter('layoutIds', $layoutIds)
            ->addSelect('bt')
            ->addSelect('i')
            ->addSelect('c')
            ->addSelect('z')
            ->addSelect('l')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC');

        if ($haveContent && ('title' === $blockType || 'title-header' === $blockType)) {
            $statement->andWhere('i.title IS NOT NULL');
        } elseif ($haveContent) {
            $statement->andWhere('i.body IS NOT NULL OR i.introduction IS NOT NULL');
        }

        $cacheKey = implode(',', $layoutIds).'-'.$blockType.'-'.$locale.'-'.($asThumb ? 'thumb' : 'no_thumb').'-'.($haveContent ? 'content' : 'no_content');
        $blocks = $statement->getQuery()
            ->enableResultCache(3600, 'block_type_batch_'.md5($cacheKey))
            ->getResult();

        $byLayout = [];
        foreach ($blocks as $block) {
            $layoutId = $block->getCol()?->getZone()?->getLayout()?->getId();
            if (null !== $layoutId) {
                $byLayout[$layoutId][] = $block;
            }
        }

        $result = [];
        foreach ($layoutIds as $layoutId) {
            $layoutBlocks = $byLayout[$layoutId] ?? [];
            $selected = $layoutBlocks[0] ?? null;
            if ($asThumb) {
                foreach ($layoutBlocks as $block) {
                    if ($block->isUseForThumb()) {
                        $selected = $block;
                        break;
                    }
                }
            }
            $result[$layoutId] = $selected;
        }

        return $result;
    }

    /**
     * Batch variant of findTitleByForceAndLocaleLayout ($all = false), keyed by layout id.
     *
     * @param int[] $layoutIds
     *
     * @return array<int, string|null>
     */
    public function findTitleByForceAndLayouts(array $layoutIds, string $locale, int $titleForce): array
    {
        if (!$layoutIds) {
            return [];
        }
        $cacheKey = implode(',', $layoutIds).'-'.$locale.'-'.$titleForce;
        $blocks = $this->createQueryBuilder('b')
            ->leftJoin('b.intls', 'i')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->andWhere('i.titleForce = :titleForce')
            ->andWhere('i.title IS NOT NULL')
            ->andWhere('i.locale = :locale')
            ->andWhere('l.id IN (:layoutIds)')
            ->setParameter('titleForce', $titleForce)
            ->setParameter('locale', $locale)
            ->setParameter('layoutIds', $layoutIds)
            ->addSelect('i')
            ->addSelect('c')
            ->addSelect('z')
            ->addSelect('l')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC')
            ->getQuery()
            ->enableResultCache(3600, 'block_title_layout_batch_'.md5($cacheKey))
            ->getResult();

        $byLayout = [];
        foreach ($blocks as $block) {
            $layoutId = $block->getCol()?->getZone()?->getLayout()?->getId();
            if (null !== $layoutId && !isset($byLayout[$layoutId])) {
                $byLayout[$layoutId] = $block;
            }
        }

        $result = [];
        foreach ($layoutIds as $layoutId) {
            $block = $byLayout[$layoutId] ?? null;
            $title = null;
            if ($block) {
                foreach ($block->getIntls() as $intl) {
                    if ($locale === $intl->getLocale()) {
                        $title = $intl->getTitle();
                        break;
                    }
                }
            }
            $result[$layoutId] = $title;
        }

        return $result;
    }

//    /**
//     * Find block text by locale & Page.
//     *
//     * @throws NonUniqueResultException
//     */
//    public function findFieldTextByLocalePage(string $field, Page $page, string $locale): ?Block
//    {
//        $cacheKey = 'field_text_'.md5($field.'_'.$page->getId().'_'.$locale);
//        if (isset($this->cache['field_text'][$cacheKey])) {
//            return $this->cache['field_text'][$cacheKey];
//        }
//
//        $result = $this->createQueryBuilder('b')
//            ->leftJoin('b.blockType', 'bt')
//            ->leftJoin('b.intls', 'i')
//            ->leftJoin('b.mediaRelations', 'bmr')
//            ->leftJoin('bmr.media', 'bmrm')
//            ->leftJoin('b.actionIntls', 'bai')
//            ->leftJoin('b.col', 'c')
//            ->leftJoin('c.zone', 'z')
//            ->leftJoin('z.layout', 'l')
//            ->leftJoin('l.page', 'p')
//            ->andWhere('bt.slug = :slug')
//            ->andWhere('i.'.$field.' IS NOT NULL')
//            ->andWhere('i.locale = :locale')
//            ->andWhere('p.id = :page')
//            ->setParameter('slug', 'media')
//            ->setParameter('locale', $locale)
//            ->setParameter('page', $page)
//            ->addSelect('bt')
//            ->addSelect('i')
//            ->addSelect('bmr')
//            ->addSelect('bmrm')
//            ->addSelect('bai')
//            ->addSelect('l')
//            ->addSelect('z')
//            ->addSelect('c')
//            ->addOrderBy('b.position', 'ASC')
//            ->addOrderBy('z.position', 'ASC')
//            ->setMaxResults(1)
//            ->getQuery()
//            ->enableResultCache(3600, $cacheKey)
//            ->getOneOrNullResult();
//
//        $getter = 'get'.ucfirst($field);
//
//        return $this->cache['field_text'][$cacheKey] = $result ? $result->getIntls()[0]->$getter() : null;
//    }

    /**
     * Find block text by locale & Page.
     */
    public function findMediaByLocalePage(Page $page, string $locale): array
    {
        $cacheKey = 'media_page_'.md5($page->getId().'_'.$locale);
        if (isset($this->cache['media_page'][$cacheKey])) {
            return $this->cache['media_page'][$cacheKey];
        }

        $result = $this->createQueryBuilder('b')
            ->leftJoin('b.mediaRelations', 'mr')
            ->leftJoin('mr.media', 'm')
            ->leftJoin('b.col', 'c')
            ->leftJoin('c.zone', 'z')
            ->leftJoin('z.layout', 'l')
            ->leftJoin('l.page', 'p')
            ->andWhere('m.originalName IS NOT NULL')
            ->andWhere('mr.locale = :locale')
            ->andWhere('p.id = :page')
            ->setParameter('locale', $locale)
            ->setParameter('page', $page->getId())
            ->addSelect('mr')
            ->addSelect('m')
            ->addSelect('l')
            ->addSelect('z')
            ->addSelect('c')
            ->addOrderBy('b.position', 'ASC')
            ->addOrderBy('z.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->enableResultCache(3600, $cacheKey)
            ->getArrayResult();

        return $this->cache['media_page'][$cacheKey] = !empty($result[0]['mediaRelations'][0]['media']) ? $result[0]['mediaRelations'][0]['media'] : [];
    }

//    /**
//     * Find by Action.
//     */
//    public function findByAction(string $classname, int $filterId): array
//    {
//        return $this->createQueryBuilder('b')
//            ->leftJoin('b.intls', 'bi')
//            ->leftJoin('b.mediaRelations', 'bmr')
//            ->leftJoin('bmr.media', 'bmrm')
//            ->leftJoin('b.action', 'a')
//            ->leftJoin('b.actionIntls', 'ai')
//            ->andWhere('a.entity = :entity')
//            ->andWhere('ai.actionFilter = :actionFilter')
//            ->setParameter('entity', $classname)
//            ->setParameter('actionFilter', $filterId)
//            ->addSelect('bi')
//            ->addSelect('bmr')
//            ->addSelect('bmrm')
//            ->addSelect('ai')
//            ->getQuery()
//            ->enableResultCache(3600, 'block_action_'.md5($classname.'_'.$filterId))
//            ->getResult();
//    }
}
