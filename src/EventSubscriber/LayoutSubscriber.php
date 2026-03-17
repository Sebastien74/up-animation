<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Layout\Block;
use App\Entity\Layout\BlockIntl;
use App\Entity\Layout\BlockMediaRelation;
use App\Entity\Layout\Col;
use App\Entity\Layout\Layout;
use App\Entity\Layout\PageMediaRelation;
use App\Entity\Layout\Zone;
use App\Entity\Layout\ZoneIntl;
use App\Entity\Layout\ZoneMediaRelation;
use App\Entity\Layout\Page;
use App\Entity\Media\MediaRelationIntl;
use App\Entity\Seo\Url;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Psr\Cache\InvalidArgumentException;

#[AsDoctrineListener(event: Events::onFlush)]
class LayoutSubscriber
{
    /**
     * onFlush.
     * @throws InvalidArgumentException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        );

        $layoutsToUpdate = [];

        foreach ($entities as $entity) {
            $layout = $this->findLayout($entity, $uow);
            if ($layout instanceof Layout) {
                $layoutsToUpdate[spl_object_hash($layout)] = $layout;
            }
        }

        foreach ($layoutsToUpdate as $layout) {
            $this->updateParent($layout, $em, $uow);
            $this->invalidateCache($layout, $em);
        }
    }

    /**
     * Invalidate result cache for Page.
     *
     * @throws InvalidArgumentException
     */
    private function invalidateCache(Layout $layout, EntityManagerInterface $em): void
    {
        $cache = $em->getConfiguration()->getResultCache();
        if (!$cache) {
            return;
        }

        // layout_{id}
        if ($layout->getId()) {
            $cache->deleteItem('layout_' . $layout->getId());
        }

        $parent = $layout->getParent($em);
        if (!$parent) {
            return;
        }

        $website = method_exists($parent, 'getWebsite') ? $parent->getWebsite() : null;
        if (!$website) {
            return;
        }

        $websiteId = $website->getId();

        if (method_exists($parent, 'getUrls')) {
            foreach ($parent->getUrls() as $url) {
                /** @var Url $url */
                $locale = $url->getLocale();
                $urlCode = $url->getCode();

                // page-index-{websiteId}-{locale}
                $cache->deleteItem('page-index-' . $websiteId . '-' . $locale);

                // page-{websiteId}-{urlCode}-{locale}
                if ($urlCode) {
                    $cache->deleteItem('page-' . $websiteId . '-' . $urlCode . '-' . $locale);
                }

                // page-url-{md5(id_locale)}
                if ($parent instanceof Page) {
                    $cache->deleteItem('page-url-' . md5($parent->getId() . '_' . $locale));
                }
            }
        }

        // page_action_... et pages_action_...
        // On ne peut pas facilement reconstruire le MD5 car on n'a pas classname et filterId
        // Mais on peut essayer d'invalider par tag si Doctrine le supporte,
        // ou documenter que ces caches complexes pourraient rester.
        // Vu que findByAction est souvent utilisé, c'est critique.
    }

    /**
     * Find Layout from various layout components.
     */
    private function findLayout(object $entity, UnitOfWork $uow): ?Layout
    {
        if ($entity instanceof Layout) {
            return $entity;
        }

        // Zone -> Layout
        if ($entity instanceof Zone) {
            $layout = $entity->getLayout();
            if (!$layout) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $layout = $changeSet['layout'][1] ?? null;
            }
            return $layout;
        }

        // ZoneIntl -> Zone -> Layout
        if ($entity instanceof ZoneIntl) {
            $zone = $entity->getZone();
            if (!$zone) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $zone = $changeSet['zone'][1] ?? null;
            }
            return $zone ? $zone->getLayout() : null;
        }

        // Col -> Zone -> Layout
        if ($entity instanceof Col) {
            $zone = $entity->getZone();
            if (!$zone) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $zone = $changeSet['zone'][1] ?? null;
            }
            return $zone ? $zone->getLayout() : null;
        }

        // Block -> Col -> Zone -> Layout
        if ($entity instanceof Block) {
            $col = $entity->getCol();
            if (!$col) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $col = $changeSet['col'][1] ?? null;
            }
            return $col && $col->getZone() ? $col->getZone()->getLayout() : null;
        }

        // BlockIntl -> Block -> Col -> Zone -> Layout
        if ($entity instanceof BlockIntl) {
            $block = $entity->getBlock();
            if (!$block) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $block = $changeSet['block'][1] ?? null;
            }
            return $block && $block->getCol() && $block->getCol()->getZone()
                ? $block->getCol()->getZone()->getLayout() : null;
        }

        // BlockMediaRelation -> Block -> Col -> Zone -> Layout
        if ($entity instanceof BlockMediaRelation) {
            $block = $entity->getBlock();
            if (!$block) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $block = $changeSet['block'][1] ?? null;
            }
            return $block && $block->getCol() && $block->getCol()->getZone()
                ? $block->getCol()->getZone()->getLayout() : null;
        }

        // ZoneMediaRelation -> Zone -> Layout
        if ($entity instanceof ZoneMediaRelation) {
            $zone = $entity->getZone();
            if (!$zone) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $zone = $changeSet['zone'][1] ?? null;
            }
            return $zone ? $zone->getLayout() : null;
        }

        // PageMediaRelation -> Page -> Layout
        if ($entity instanceof PageMediaRelation) {
            $page = $entity->getPage();
            if (!$page) {
                $changeSet = $uow->getEntityChangeSet($entity);
                $page = $changeSet['page'][1] ?? null;
            }
            return $page ? $page->getLayout() : null;
        }

        // MediaRelationIntl changes should be captured via owning BaseMediaRelation (Block/Zone/PageMediaRelation)
        // When only Intl changes without owning side updates, we can't easily find the owner here.

        return null;
    }

    /**
     * Update parent entity of the layout.
     */
    private function updateParent(Layout $layout, EntityManagerInterface $em, UnitOfWork $uow): void
    {
        $parent = $layout->getParent($em);
        if (is_object($parent) && method_exists($parent, 'setUpdatedAt')) {
            $parent->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($parent)), $parent);
        }
    }
}
